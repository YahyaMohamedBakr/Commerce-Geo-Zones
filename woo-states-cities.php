
<?php
/**
 * Plugin Name: woo states and cities
 * Description: WooCommerce Plugin to replace countries and cities with drive google cheets
 * Author: Motaweroon
 * Author URI: https://Motaweroon.com/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.2
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */




//require (ABSPATH. '/vendor/autoload.php');
require (ABSPATH.'/wp-content/plugins/woo-states-cities/vendor/autoload.php');

// Get the API client and construct the service object.

function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Yahya-Apps');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
    //PATH TO JSON FILE DOWNLOADED FROM GOOGLE CONSOLE FROM STEP 7
    $client->setAuthConfig(ABSPATH.'/credentials.json'); 
    $client->setAccessType('offline');
    return $client;
}

$client = getClient();
$service = new Google_Service_Sheets($client);
$spreadsheetId = '15sWyGtd-faQChXqCkXB85E_LsCZ_vlYM1wj8pY8IJiw'; // spreadsheet Id
$range_rows = "Sheet1!A1:Z1";
$range_col = "Sheet1!A2:z";
$col= "COLUMNS"; // Sheet name
$ro = "ROWS";

$valueRange= new Google_Service_Sheets_ValueRange();
//$valueRange->setValues(["values" => ["a", "j"]]); // values for each cell

    $columns = $service->spreadsheets_values->get($spreadsheetId, $range_col, array("majorDimension"=>$col))->getValues();
    $rows = $service->spreadsheets_values->get($spreadsheetId, $range_rows, array("majorDimension"=>$ro))->getValues()[0];
    
   
// Replace states
 add_filter( 'woocommerce_states', 'woo_custom_woocommerce_states' );
 function woo_custom_woocommerce_states( $states ) {
    global $rows;
    if ( !( $rows )) {
        $states['EG'] = array(
            '0' => 'يتعذر تحميل المحافظات يرجى المحاولة لاحقا'
        );
        return $states;

    }else{
    
  
    $states['EG'] = array();
    foreach ($rows as $state_key=>$state_value) {
        $states['EG'][($state_key+1)] = $state_value ;  
    }
    //var_dump($states) ;
    return $states;
    }   
}


//change city field to select element in client side

add_filter( 'woocommerce_billing_fields' , 'woo_client_billing_edit');
function woo_client_billing_edit( $fields ) {
    $option_cities = array(
       
            "0"=> "حدد خياراً"
         );
    // Set billing city field as select dropdown
    $fields['billing_city']['type'] = 'select';
    $fields['billing_city']['options'] =  $option_cities;
    return $fields;
}

add_filter( 'woocommerce_shipping_fields' , 'woo_client_shipping_edit');
 function woo_client_shipping_edit( $fields ) {
    $option_cities = array(
       
            "0"=> "حدد خياراً"
         );
    // Set billing city field as select dropdown
    $fields['shipping_city']['type'] = 'select';
    $fields['shipping_city']['options'] = $option_cities;

    return $fields;
}


//add cities to select element based on data from turbo in client side

add_action( 'wp_enqueue_scripts', 'woo_custom_client_js_script' );
function woo_custom_client_js_script() {

    $current_url = $_SERVER['REQUEST_URI'];
    if (( is_checkout() && ! is_wc_endpoint_url() )||strpos($current_url, '/billing') !== false || strpos($current_url, '/shipping') !== false) {
        $woo=WC(); //woocommerce object
        $selected_billing_city= $woo->customer->get_billing_city();
        $selected_shipping_city = $woo->customer->get_shipping_city();
        
        wp_enqueue_script('custom-client-script', plugin_dir_url( __FILE__ ) . '/script.js', array('jquery'), '1.0', true);
        wp_localize_script( 'custom-client-script', 'custom_client_script_vars', array(
            //pass values to the script file
            'site_url' => get_site_url(),
            'selected_billing_city'=>  $selected_billing_city,
            'selected_shipping_city'=> $selected_shipping_city ,
            

        ));
    }
}


//endpoint for get areas
add_action( 'rest_api_init', function () {
    register_rest_route( 'woo','/getareas', array(
      'methods' => 'GET',
      'callback' => 'woo_get_areas',
      'permission_callback' => '__return_true'
    ));
  });
  
  function woo_get_areas(){
      global $columns;
     // $id = isset($_GET['id'])? $_GET['id'] : '';
      return $columns[$_GET['id']];
  }

