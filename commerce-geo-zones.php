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


require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require plugin_dir_path( __FILE__ ) . 'geo-zone-options.php';
include_once('classes.php');



function cgzones_getClient(){
  

    //$app_name = sanitize_text_field( wp_unslash (@$_POST['app_name']));

    $app_name=get_option('app_name');
    try{
        $client = new Google_Client();
        $client->setApplicationName($app_name);
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $jsonOption = get_option('credentials_file');
        $jsonOption = json_decode($jsonOption, true);
        $client->setAuthConfig($jsonOption); 
        //$client->setAccessType('offline');
        return $client;
    }
    catch(Exception $e){
        return null;
    }
   
}

function getGoogleSheetData($type){
    $client = cgzones_getClient();
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
 



    // Replace states

if(get_option('cgzones_enable_states', true) && !empty(cgzones_getClient())){

    add_filter( 'woocommerce_states', 'cgzones_custom_woocommerce_states' );
}

 function cgzones_custom_woocommerce_states( $states ) {

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



$clientBilling= new Cgzones_client('billing_city');
$clientShipping= new Cgzones_client('shipping_city');


//change city field to select element in client side
if(get_option('cgzones_enable_cities', true)){
add_filter( 'woocommerce_billing_fields' , array($clientBilling,'cityDropdown'));
add_filter( 'woocommerce_shipping_fields' , array ($clientShipping, 'cityDropdown'));
add_action( 'wp_enqueue_scripts', array ($clientShipping, 'addScript') );
}




$adminBilling = new Cgzones_admin('billing_city');
$adminShipping = new Cgzones_admin ('shipping_city');
//admin side 

if(get_option('cgzones_enable_admin', true)){
    add_filter( 'woocommerce_admin_billing_fields' , array($adminBilling,'cityDropdown') );
    add_filter( 'woocommerce_admin_shipping_fields' , array($adminShipping,'cityDropdown') );
    add_action( 'admin_enqueue_scripts', array($adminBilling, 'addScript') );
}



//endpoint for get areas
add_action( 'rest_api_init', function () {
    register_rest_route( 'cgzones','/getareas', array(
      'methods' => 'GET',
      'callback' => 'cgzones_get_areas',
      'permission_callback' => '__return_true'
    ));
  });
  

  function cgzones_get_areas(){
      $data = getGoogleSheetData('COLUMNS')['COLUMNS'];
        if (isset($_GET['id']) ) {
           
            $d= $data[$_GET['id']];
            return  $data[$_GET['id']] ;
            } else {
                return null;
            }

     
      
  }



