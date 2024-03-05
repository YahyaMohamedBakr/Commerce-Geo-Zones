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

// require (ABSPATH.'/wp-content/plugins/Commerce-Geo-Zones/vendor/autoload.php');
// require (ABSPATH.'/wp-content/plugins/Commerce-Geo-Zones/geo-zone-options.php');
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require plugin_dir_path( __FILE__ ) . 'geo-zone-options.php';
include_once('classes.php');

//credentials file create
//     $upload_dir   = wp_upload_dir();
//     if (empty($upload_dir['basedir'])) return;
//     $credentials_dirname = $upload_dir['basedir'].'/credentials';
//     if (!file_exists($credentials_dirname)) {
//         wp_mkdir_p($credentials_dirname);
//     }
// add_action('admin_post_save_credentials_file', 'save_credentials_file');

// function save_credentials_file() {
//     if (isset($_FILES['credentials_upload']) && !empty($_FILES['credentials_upload']['name'])) {
//         $file = $_FILES['credentials_upload'];

//         $upload_dir = wp_upload_dir();
//         $target_dir = $upload_dir['basedir'] . '/credentials';
//         $target_file = $target_dir . basename($file['name']);
//         $upload_ok = 1;
//         $file_type = pathinfo($target_file, PATHINFO_EXTENSION);

//         // Check if file already exists
//         if (file_exists($target_file)) {
//             $upload_ok = 0;
//         }

//         // Check file size (example: limit to 5MB)
//         if ($file['size'] > 5 * 1024 * 1024) {
//             $upload_ok = 0;
//         }

//         // Allow certain file formats (example: allow only JSON files)
//         if ($file_type != 'json') {
//             $upload_ok = 0;
//         }

//         // Check if $upload_ok is set to 0 by an error
//         if ($upload_ok == 0) {
//             // Handle errors
//         } else {
//             if (!file_exists($target_dir)) {
//                 mkdir($target_dir, 0755, true);
//             }

//             if (move_uploaded_file($file['tmp_name'], $target_file)) {
//                 update_option('credentials_file', $target_file);
//             }
//         }
//     }

//     // Redirect back to settings page
//     wp_redirect(admin_url('admin.php?page=Commerce-Geo-Zones'));
//     exit;
// }


function cgz_getClient(){
    // require_once( ABSPATH . 'wp-includes/pluggable.php' );
    // $nonce =  wp_verify_nonce(sanitize_text_field( wp_unslash ( @$_POST['_wpnonce'])));
    // if (empty($nonce)) return ;

    $app_name = sanitize_text_field( wp_unslash (@$_POST['app_name']));
    try{
        $client = new Google_Client();
        $client->setApplicationName($app_name);
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        //PATH TO JSON FILE DOWNLOADED FROM GOOGLE CONSOLE FROM STEP 7
        //$client->setAuthConfig(ABSPATH.'wp-content/uploads/credentials/credentials.json'); 
        $jsonOption = get_option('credentials_file');
        $jsonOption = json_decode($jsonOption, true);
        $client->setAuthConfig($jsonOption); 


        //$c = $client->setAuthConfig(get_option('credentials_file'));
        //$client->setAccessType('offline');
        return $client;
    }
    catch(Exception $e){
        return null;
    }
   
}

//add_action( 'woocommerce_checkout_before_customer_details','updateGoogleSheet');
function getGoogleSheetData($type){
    $client = cgz_getClient();
    if (empty($client)) return;

    $service = new Google_Service_Sheets($client);
    $spreadsheetId = get_option('sheet_id'); // spreadsheet Id
    $range_rows = "Sheet1!A1:Z1";
    $range_col = "Sheet1!A2:z";
    // $col= "COLUMNS"; // Sheet name
    // $ro = "ROWS";
    
    try {
        $columns = [];
        if (!empty($type)){
            $columns = $service->spreadsheets_values->get($spreadsheetId, $range_col, array("majorDimension"=>$type))->getValues();
        }
        $rows = [];
        if (!empty($type)){
            $rows = $service->spreadsheets_values->get($spreadsheetId, $range_rows, array("majorDimension"=>$type))->getValues()[0];
        }
      
        return ['COLUMNS'=>$columns , 'ROWS'=>$rows];
    } catch (Google\Service\Exception $e) {
        return false;
    }
}
 

// var_dump(getGoogleSheetData('ROWS'));
// die();

    // Replace states

if(get_option('cgz_enable_states', true) && !empty(cgz_getClient())){

    add_filter( 'woocommerce_states', 'cgz_custom_woocommerce_states' );
}

 function cgz_custom_woocommerce_states( $states ) {

    $data = getGoogleSheetData('ROWS')['ROWS'];
    if ( !( $data )) {
        $states['EG'] = array(
            '0' => 'يتعذر تحميل المحافظات يرجى المحاولة لاحقا'
        );
        return $states;

    }else{

    $states['EG'] = array();
    foreach ($data as $state_key=>$state_value) {
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
      $data = getGoogleSheetData('COLUMNS')['COLUMNS'];
        if (isset($_GET['id']) ) {
           
            $d= $data[$_GET['id']];
            return  $data[$_GET['id']] ;
            } else {
                return null;
            }

     
      
  }



