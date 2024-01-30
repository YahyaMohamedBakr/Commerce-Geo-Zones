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


require (ABSPATH.'/wp-content/plugins/Commerce-Geo-Zones/vendor/autoload.php');
include_once('classes.php');

// credentials file create
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

if(get_option('cgz_enable_states', true)){

    add_filter( 'woocommerce_states', 'cgz_custom_woocommerce_states' );
}

 function cgz_custom_woocommerce_states( $states ) {

    
    global $rows;
    if ( !( $rows )) {
        $states['IQ'] = array(
            '0' => 'يتعذر تحميل المحافظات يرجى المحاولة لاحقا'
        );
        return $states;

    }else{

    $states['IQ'] = array();
    foreach ($rows as $state_key=>$state_value) {
        $states['IQ'][($state_key+1)] = $state_value ;  
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



//Plugin Settings page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cgz_settings_page');

function cgz_settings_page($links)
{
   $links[] = '<a href="' . esc_url(admin_url('admin.php?page=cgz')) . '">' . esc_html__('Settings', 'cgz') . '</a>';
   return $links;
}

//cgz in side menu
add_action( 'admin_menu', 'cgz_menu' );
function cgz_menu() {
    add_menu_page(
        'Commerce Geo Zones', // page title
        'Commerce Geo Zones', // menu title
        'manage_options', // permisions
        'Commerce-Geo-Zones', // slug
         'cgz_options_page', // page function
        //  plugin_dir_url( __FILE__ ).'/img/favicon.png',// logo
        //  56 // menu position
    );
}

//main style sheet
add_action('admin_print_styles', 'cgz_stylesheet');

function cgz_stylesheet()
{
   wp_enqueue_style('cgz_style', plugins_url('/css/main.css', __FILE__));
}

  add_action( 'admin_init', 'cgz_register_settings' );
function cgz_register_settings() {

    register_setting('cgz_options_group', 'app_name');
    register_setting('cgz_options_group', 'sheet_id');
    register_setting('cgz_options_group', 'credentials_file');
    register_setting('cgz_options_group', 'cgz_enable_states');
    register_setting('cgz_options_group', 'cgz_enable_cities');
    register_setting('cgz_options_group', 'cgz_enable_admin');

    
}

if (isset($_POST["credentials_file"])) {
    
    
    $file = fopen(ABSPATH.'wp-content/uploads/credentials/credentials.json','w');
    fwrite($file, get_option('credentials_file') );
    fclose($file);
}


function cgz_options_page() { ?>
    <div class="wrap">
        <h2>Commerce Geo Zones Settings</h2>
        <form method="post"  action="options.php" >
            <?php settings_fields('cgz_options_group'); ?>
            <?php do_settings_sections( 'cgz_options_group' ); ?>

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
                    <th><label for="cgz_enable_states">Enable states ?</label></th>
                    <td>
                    <select class="regular-text" name="cgz_enable_states" id="cgz_enable_states">
                        <option value ="1" <?php selected( get_option( 'cgz_enable_states' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'cgz_enable_states' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cgz_enable_states">Enable cities ?</label></th>
                    <td>
                    <select class="regular-text" name="cgz_enable_cities" id="cgz_enable_cities">
                        <option value ="1" <?php selected( get_option( 'cgz_enable_cities' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'cgz_enable_cities' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cgz_enable_states">Enable cities in Admin side ?</label></th>
                    <td>
                    <select class="regular-text" name="cgz_enable_admin" id="cgz_enable_admin">
                        <option value ="1" <?php selected( get_option( 'cgz_enable_admin' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'cgz_enable_admin' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </div>
       
        <?php 
    }
    
    ?>