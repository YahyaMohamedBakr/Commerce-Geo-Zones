<?php
/**
 * Plugin Name: Woo Geo Zones
 * Description: WooCommerce Plugin to replace countries and cities with drive google cheets
 * Author: Yahya Bakr
 * Author URI: https://Motaweroon.com/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.2
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


require (ABSPATH.'/wp-content/plugins/WooGeoZones/vendor/autoload.php');


    $upload_dir   = wp_upload_dir();
    if (empty($upload_dir['basedir'])) return;
    $credentials_dirname = $upload_dir['basedir'].'/credentials';
    if (!file_exists($credentials_dirname)) {
        wp_mkdir_p($credentials_dirname);
    }

function getClient(){
    try{
        $client = new Google_Client();
        $client->setApplicationName(get_option('app_name'));
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        //PATH TO JSON FILE DOWNLOADED FROM GOOGLE CONSOLE FROM STEP 7
        $client->setAuthConfig(ABSPATH . 'wp-content/uploads/credentials/credentials.json'); 
        $client->setAccessType('offline');
        return $client;
    }
    catch(Exception $e){
        return null;
    }
   
}

 $client = getClient();
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

if(get_option('wgz_enable_states', true)){

    add_filter( 'woocommerce_states', 'wgz_custom_woocommerce_states' );
}

 function wgz_custom_woocommerce_states( $states ) {

    
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


//change city field to select element in client side
if(get_option('wgz_enable_cities', true)){
add_filter( 'woocommerce_billing_fields' , 'wgz_client_billing_edit');
add_filter( 'woocommerce_shipping_fields' , 'wgz_client_shipping_edit');
add_action( 'wp_enqueue_scripts', 'wgz_custom_client_js_script' );
}
function wgz_client_billing_edit( $fields ) {
    $option_cities = array(
       
            "0"=> "حدد خياراً"
         );
// Set billing city field as select dropdown
    $fields['billing_city']['type'] = 'select';
    $fields['billing_city']['options'] =  $option_cities;
    return $fields;
}


 function wgz_client_shipping_edit( $fields ) {
    $option_cities = array(
       
            "0"=> "حدد خياراً"
         );
// Set billing city field as select dropdown
    $fields['shipping_city']['type'] = 'select';
    $fields['shipping_city']['options'] = $option_cities;

    return $fields;
}

//add cities to select element based on data from wgz in client side

function wgz_custom_client_js_script() {

    $current_url = $_SERVER['REQUEST_URI'];
    if (( is_checkout() && ! is_wc_endpoint_url() )||strpos($current_url, '/billing') !== false || strpos($current_url, '/shipping') !== false) {
        $woo=WC(); //woocommerce object
        $selected_billing_city= $woo->customer->get_billing_city();
        $selected_shipping_city = $woo->customer->get_shipping_city();
        
        wp_enqueue_script('custom-client-script', plugin_dir_url( __FILE__ ) . '/js/client.js', array('jquery'), '1.0', true);
        wp_localize_script( 'custom-client-script', 'custom_client_script_vars', array(

        //pass values to the script file
            'site_url' => get_site_url(),
            'selected_billing_city'=>  $selected_billing_city,
            'selected_shipping_city'=> $selected_shipping_city ,
            

        ));
    }
}



//admin side 

if(get_option('wgz_enable_admin', true)){
    add_filter( 'woocommerce_admin_billing_fields' , 'wgz_admin_billing_edit' );
    add_filter( 'woocommerce_admin_shipping_fields' , 'wgz_admin_shipping_edit' );
    add_action( 'admin_enqueue_scripts', 'wgz_admin_side_script' );
}

 function wgz_admin_billing_edit( $fields ) {
    $order = wc_get_order();
    $selected_billing =$order->get_billing_city();
    $selected_billing_city =explode(':',$selected_billing) ;
    $selected_billing_city_name =$selected_billing_city[1];
    $selected_billing_city_value = $selected_billing_city[0];
    
    if( !$selected_billing_city_name || !$selected_billing_city_value){
        $option_cities = array(
        
            '0' => 'اختر مدينة'
        );
    }else{
        $option_cities = array(
            $selected_billing_city_value.':'.$selected_billing_city_name => $selected_billing_city_name,
            '0'=>'جارٍ تحميل بقية المدن'
        );
    }

    
     // Set billing city field as select dropdown
     $fields['city']['type'] = 'select';
     $fields['city']['options'] = $option_cities;
 
     return $fields;
 }

 function wgz_admin_shipping_edit( $fields ) {
    $order = wc_get_order();
    $selected_shipping = $order->get_shipping_city();
    $selected_shipping_city =explode(':',$selected_shipping) ;
    $selected_shipping_city_name =$selected_shipping_city[1];
    $selected_shipping_city_value = $selected_shipping_city[0];
    

    if( !$selected_shipping_city_name || ! $selected_shipping_city_value){
        $option_cities = array(
        
            '0' => 'اختر مدينة'
        );
    }else{

        $option_cities = array(
            
            $selected_shipping_city_value.':'.$selected_shipping_city_name => $selected_shipping_city_name,
            '0'=>'جارٍ تحميل بقية المدن'
        );
    }
    
    // Set billing city field as select dropdown
    $fields['city']['type'] = 'select';
    $fields['city']['options'] = $option_cities;

    return $fields;
}

// add cities to select element based on data from wgz in admin side 
function wgz_admin_side_script() {
   
    $order = wc_get_order();
    
    
    if($order){
    //cities values
    $selected_billing_city_arr =explode(':',$order->get_billing_city());
    $selected_billing_city_name =$selected_billing_city_arr[1];
    $selected_billing_city_value = $selected_billing_city_arr[0];
    $selected_shipping_city_arr =explode(':',$order->get_shipping_city()) ;
    $selected_shipping_city_name =$selected_shipping_city_arr[1];
    $selected_shipping_city_value = $selected_shipping_city_arr[0];
    //states values
    $selected_billing_state_value =$order->get_billing_state();
    $selected_shipping_state_value = $order->get_shipping_state();

    wp_enqueue_script( 'wgz-admin-side-script', plugin_dir_url( __FILE__ ) . '/js/admin.js', array( 'jquery' ), '1.0', true );
    wp_localize_script( 'wgz-admin-side-script', 'wgz_admin_side_script_vars', array(
        //pass values to the script file
        'site_url' => get_site_url(),
        'selected_billing_city_name'=>$selected_billing_city_name,
        'selected_billing_city_value'=>$selected_billing_city_value,
        'selected_shipping_city_name'=> $selected_shipping_city_name,
        'selected_shipping_city_value'=> $selected_shipping_city_value,
        'selected_billing_state_value'=> $selected_billing_state_value,
        'selected_shipping_state_value'=> $selected_shipping_state_value,
    ));
}
}


//endpoint for get areas
add_action( 'rest_api_init', function () {
    register_rest_route( 'wgz','/getareas', array(
      'methods' => 'GET',
      'callback' => 'wgz_get_areas',
      'permission_callback' => '__return_true'
    ));
  });
  
  function wgz_get_areas(){
      global $columns;
      return $columns[$_GET['id']];
  }



//Plugin Settings page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wgz_settings_page');

function wgz_settings_page($links)
{
   $links[] = '<a href="' . esc_url(admin_url('admin.php?page=wgz')) . '">' . esc_html__('Settings', 'wgz') . '</a>';
   return $links;
}

//wgz in side menu
add_action( 'admin_menu', 'wgz_menu' );
function wgz_menu() {
    add_menu_page(
        'Woo Geo Zones', // page title
        'Woo Geo Zones', // menu title
        'manage_options', // permisions
        'WooGeoZones', // slug
         'wgz_options_page', // page function
        //  plugin_dir_url( __FILE__ ).'/img/favicon.png',// logo
        //  56 // menu position
    );
}

//main style sheet
add_action('admin_print_styles', 'wgz_stylesheet');

function wgz_stylesheet()
{
   wp_enqueue_style('wgz_style', plugins_url('/css/main.css', __FILE__));
}

  add_action( 'admin_init', 'wgz_register_settings' );
function wgz_register_settings() {

    register_setting('wgz_options_group', 'app_name');
    register_setting('wgz_options_group', 'sheet_id');
    register_setting('wgz_options_group', 'credentials_file');
    register_setting('wgz_options_group', 'wgz_enable_states');
    register_setting('wgz_options_group', 'wgz_enable_cities');
    register_setting('wgz_options_group', 'wgz_enable_admin');

    
}

if (isset($_POST["credentials_file"])) {
    
    
    $file = fopen(ABSPATH.'wp-content/uploads/credentials/credentials.json','w');
    fwrite($file, get_option('credentials_file') );
    fclose($file);
}


function wgz_options_page() { ?>
    <div class="wrap">
        <h2>WooGeoZones Settings</h2>
        <form method="post"   >
            <?php settings_fields('wgz_options_group'); ?>
            <?php do_settings_sections( 'wgz_options_group' ); ?>

            <table class="form-table">
                <tr>
                    <th><label for="app_name">Google Application Name:</label></th>
                    <td>
                        <input  type = 'text' class="regular-text" id="app_name" name="app_name" value="<?php echo get_option('app_name'); ?>" style="<?php echo empty(get_option('app_name')) ? 'border: 1px solid red' : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="sheet_id">Google Sheet Id:</label></th>
                    <td>
                        <input  type = 'text' class="regular-text" id="sheet_id" name="sheet_id" value="<?php echo get_option('sheet_id'); ?>" style="<?php echo empty(get_option('sheet_id')) ? 'border: 1px solid red' : ''; ?>">
                    </td>
                </tr>
                
                <tr>
                    <th><label for="credentials_file">Google credentials file:</label></th>
                    <td>
                        <textarea class="regular-text" name="credentials_file" id="credentials_file" style ="height:150px; <?php echo empty(get_option('credentials_file')) ? 'border: 1px solid red' : ''; ?>">
                            <?php echo get_option('credentials_file'); ?>
                        </textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="wgz_enable_states">Enable states ?</label></th>
                    <td>
                    <select class="regular-text" name="wgz_enable_states" id="wgz_enable_states">
                        <option value ="1" <?php selected( get_option( 'wgz_enable_states' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'wgz_enable_states' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="wgz_enable_states">Enable cities ?</label></th>
                    <td>
                    <select class="regular-text" name="wgz_enable_cities" id="wgz_enable_cities">
                        <option value ="1" <?php selected( get_option( 'wgz_enable_cities' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'wgz_enable_cities' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="wgz_enable_states">Enable cities in Admin side ?</label></th>
                    <td>
                    <select class="regular-text" name="wgz_enable_admin" id="wgz_enable_admin">
                        <option value ="1" <?php selected( get_option( 'wgz_enable_admin' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'wgz_enable_admin' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </div>
       
        <?php 
    }

    ?>