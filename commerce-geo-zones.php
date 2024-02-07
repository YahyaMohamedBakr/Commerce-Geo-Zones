<?php
/**
 * Plugin Name: Commerce geographic Zones
 * Description:  Plugin to replace WooCommerce countries and cities with drive google cheets
 * Author: Yahya Bakr
 * Author URI: https://Motaweroon.com/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.2
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

require (ABSPATH.'/wp-content/plugins/Commerce-Geo-Zones/vendor/autoload.php');
require (ABSPATH.'/wp-content/plugins/Commerce-Geo-Zones/geo-zone-options.php');
include_once('classes.php');

// credentials file create
    // $upload_dir   = wp_upload_dir();
    // if (empty($upload_dir['basedir'])) return;
    // $credentials_dirname = $upload_dir['basedir'].'/credentials';
    // if (!file_exists($credentials_dirname)) {
    //     wp_mkdir_p($credentials_dirname);
    // }

function cgz_getClient(){
    $app_name = isset($_POST['app_name']) ? sanitize_text_field($_POST['app_name']) : '';

    try{
        $client = new Google_Client();
        $client->setApplicationName($app_name);
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        //PATH TO JSON FILE DOWNLOADED FROM GOOGLE CONSOLE FROM STEP 7
        $client->setAuthConfig(ABSPATH.'wp-content/uploads/credentials/credentials.json'); 
        //$c = $client->setAuthConfig(get_option('credentials_file'));
        //$client->setAccessType('offline');
        return $client;
    }
    catch(Exception $e){
        return null;
    }
   
}

 $client = cgz_getClient();
 if ($client){

    $service = new Google_Service_Sheets($client);
    $spreadsheetId = get_option('sheet_id'); // spreadsheet Id
    $range_rows = "Sheet1!A1:Z1";
    $range_col = "Sheet1!A2:z";
    $col= "COLUMNS"; // Sheet name
    $ro = "ROWS";
    
    try {
        $columns = $service->spreadsheets_values->get($spreadsheetId, $range_col, array("majorDimension"=>$col))->getValues();
        $rows = $service->spreadsheets_values->get($spreadsheetId, $range_rows, array("majorDimension"=>$ro))->getValues()[0];
    
    // Rest of your code
    
    } catch (Google\Service\Exception $e) {
        echo '<div class="notice notice-error" style="direction:rtl"><p><strong>تنبيه:</strong> حدث خطأ أثناء الوصول إلى ورقة جوجل. يُرجى التحقق من صحة معرّف الورقة sheetid والاعتمادات credentials fileوالمحاولة مرة أخرى.</p></div>';
    }

 }


    // Replace states

if(get_option('cgz_enable_states', true)){

    add_filter( 'woocommerce_states', 'cgz_custom_woocommerce_states' );
}

 function cgz_custom_woocommerce_states( $states ) {

    
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
    return $states;
    }   
}



$clientBilling= new client('billing_city');
$clientShipping= new client('shipping_city');


//change city field to select element in client side
if(get_option('cgz_enable_cities', true)){
add_filter( 'woocommerce_billing_fields' , array($clientBilling,'cityDropdown'));
add_filter( 'woocommerce_shipping_fields' , array ($clientShipping, 'cityDropdown'));
add_action( 'wp_enqueue_scripts', array ($clientShipping, 'addScript') );
}




$adminBilling = new admin('billing_city');
$adminShipping = new admin ('shipping_city');
//admin side 

if(get_option('cgz_enable_admin', true)){
    add_filter( 'woocommerce_admin_billing_fields' , array($adminBilling,'cityDropdown') );
    add_filter( 'woocommerce_admin_shipping_fields' , array($adminShipping,'cityDropdown') );
    add_action( 'admin_enqueue_scripts', array($adminBilling, 'addScript') );
}



//endpoint for get areas
add_action( 'rest_api_init', function () {
    register_rest_route( 'cgz','/getareas', array(
      'methods' => 'GET',
      'callback' => 'cgz_get_areas',
      'permission_callback' => '__return_true'
    ));
  });
  
  function cgz_get_areas(){
      global $columns;
      return $columns[$_GET['id']];
  }



