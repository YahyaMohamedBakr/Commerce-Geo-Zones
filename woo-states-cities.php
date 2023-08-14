
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


// function createCredentialsFolder() {
//     $credentialsDir = ABSPATH . '/wp-content/credentials';
    
//     if (!file_exists($credentialsDir)) {
//         mkdir($credentialsDir, 0755, true);
//     }
// }


// Get the API client and construct the service object.


function getClient(){
    try{
        // $path = get_option('credentials_file');
        $client = new Google_Client();
        $client->setApplicationName(get_option('app_name'));
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        //PATH TO JSON FILE DOWNLOADED FROM GOOGLE CONSOLE FROM STEP 7
        //if(file_exists($path)){$client->setAuthConfig($path);}
        $client->setAuthConfig(ABSPATH . '/credentials.json'); 
        $client->setAccessType('offline');
        return $client;
    }
    catch(Exception $e){
        return null;
    }
   
}

// try {
//     $client = getClient();

//     if ($client) {
//         $service = new Google_Service_Sheets($client);

//         // Your code to get $columns and $rows

//         // Rest of your code

//     } else {
//         echo '<div class="notice notice-error"><p><strong>تنبيه:</strong> حدث خطأ أثناء تهيئة العميل للوصول إلى جوجل. يُرجى التحقق من صحة ملف الاعتمادات ومحاولة مرة أخرى.</p></div>';
//     }
// } catch (Google\Service\Exception $e) {
//     echo '<div class="notice notice-error" style="direction:rtl"><p><strong>تنبيه:</strong> حدث خطأ أثناء الوصول إلى ورقة جوجل. يُرجى التحقق من صحة معرّف الورقة sheetid والاعتمادات credentials file والمحاولة مرة أخرى.</p></div>';
// }

 $client = getClient();
 if ($client){

    $service = new Google_Service_Sheets($client);
    $spreadsheetId = get_option('sheet_id'); // spreadsheet Id
    $range_rows = "Sheet1!A1:Z1";
    $range_col = "Sheet1!A2:z";
    $col= "COLUMNS"; // Sheet name
    $ro = "ROWS";
    
    //$valueRange= new Google_Service_Sheets_ValueRange();
    //$valueRange->setValues(["values" => ["a", "j"]]); // values for each cell
    
    try {
        $columns = $service->spreadsheets_values->get($spreadsheetId, $range_col, array("majorDimension"=>$col))->getValues();
        $rows = $service->spreadsheets_values->get($spreadsheetId, $range_rows, array("majorDimension"=>$ro))->getValues()[0];
    
        // Rest of your code
    
    } catch (Google\Service\Exception $e) {
        echo '<div class="notice notice-error" style="direction:rtl"><p><strong>تنبيه:</strong> حدث خطأ أثناء الوصول إلى ورقة جوجل. يُرجى التحقق من صحة معرّف الورقة sheetid والاعتمادات credentials fileوالمحاولة مرة أخرى.</p></div>';
    }

 }


//    if(!$columns || !$rows){
//     echo "error";
//    }


// Replace states

if(get_option('woo_enable_states', true)){

    add_filter( 'woocommerce_states', 'woo_custom_woocommerce_states' );
}

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
if(get_option('woo_enable_cities', true)){
add_filter( 'woocommerce_billing_fields' , 'woo_client_billing_edit');
add_filter( 'woocommerce_shipping_fields' , 'woo_client_shipping_edit');
add_action( 'wp_enqueue_scripts', 'woo_custom_client_js_script' );
}
function woo_client_billing_edit( $fields ) {
    $option_cities = array(
       
            "0"=> "حدد خياراً"
         );
    // Set billing city field as select dropdown
    $fields['billing_city']['type'] = 'select';
    $fields['billing_city']['options'] =  $option_cities;
    return $fields;
}


 function woo_client_shipping_edit( $fields ) {
    $option_cities = array(
       
            "0"=> "حدد خياراً"
         );
    // Set billing city field as select dropdown
    $fields['shipping_city']['type'] = 'select';
    $fields['shipping_city']['options'] = $option_cities;

    return $fields;
}


//add cities to select element based on data from woo in client side

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



//Plugin Settings page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woo_settings_page');

function woo_settings_page($links)
{
   $links[] = '<a href="' . esc_url(admin_url('admin.php?page=woo')) . '">' . esc_html__('Settings', 'woo') . '</a>';

    return $links;
}

//woo in side menu
add_action( 'admin_menu', 'woo_menu' );
function woo_menu() {
    add_menu_page(
        'woo', // page title
        'woo', // menu title
        'manage_options', // permisions
        'woo', // slug
        'woo_options_page', // page function
         //plugin_dir_url( __FILE__ ).'/img/favicon.png',// logo
         56 // menu position
    );
}

//main style sheet
add_action('admin_print_styles', 'woo_stylesheet');

function woo_stylesheet()
{
   wp_enqueue_style('woo_style', plugins_url('/css/main.css', __FILE__));
}



  add_action( 'admin_init', 'woo_register_settings' );
function woo_register_settings() {

    register_setting('woo_options_group', 'app_name');
    register_setting('woo_options_group', 'sheet_id');
    register_setting('woo_options_group', 'credentials_file');
    register_setting('woo_options_group', 'woo_enable_states');
    register_setting('woo_options_group', 'woo_enable_cities');
}

// if (isset($_POST["credentials_file"])) {
//     $upload_dir   = wp_upload_dir();
//     if (empty( $upload_dir['basedir'] ) ) return;
//     $yy = $upload_dir['basedir'];
//     $credintials_dirname = $upload_dir['basedir'].'/credintials';
//     if ( ! file_exists( $credintials_dirname ) ) {
//         wp_mkdir_p( $credintials_dirname );
//     }


  
//     if (move_uploaded_file($_FILES["credentials_file"]["tmp_name"], $credintials_dirname.'/credintials.json')) {
//         echo "The file ". htmlspecialchars( basename( $_FILES["credentials_file"]["name"])). " has been uploaded.";
//     } else {
//     echo "Sorry, there was an error uploading your file.";
//     }
   
// }

if (isset($_POST["credentials_file"])) {
    $upload_dir   = wp_upload_dir();
    if (empty($upload_dir['basedir'])) return;
    $credentials_dirname = $upload_dir['basedir'].'/credentials';
    if (!file_exists($credentials_dirname)) {
        wp_mkdir_p($credentials_dirname);
    }
    
    $target_file = $credentials_dirname . '/credentials.json';
    if (move_uploaded_file($_FILES["credentials_file"]["tmp_name"], $target_file)) {
        echo "The file ". htmlspecialchars(basename($_FILES["credentials_file"]["name"])). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file: " . $_FILES["credentials_file"]["error"];
    }
}




function woo_options_page() { ?>
    <div class="wrap">
        <h2>woo Settings</h2>
        <form method="post" action="options.php" enctype="multipart/form-data" >
            <?php settings_fields('woo_options_group'); ?>
            <?php do_settings_sections( 'woo_options_group' ); ?>

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
                        <input type="file" name="credentials_file" id="credentials_file" >
                    </td>
                </tr>
                <tr>
                    <th><label for="woo_enable_states">Enable states ?</label></th>
                    <td>
                    <select class="regular-text" name="woo_enable_states" id="woo_enable_states">
                        <option value ="1" <?php selected( get_option( 'woo_enable_states' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'woo_enable_states' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="woo_enable_states">Enable cities ?</label></th>
                    <td>
                    <select class="regular-text" name="woo_enable_cities" id="woo_enable_cities">
                        <option value ="1" <?php selected( get_option( 'woo_enable_cities' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'woo_enable_cities' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </div>
        <?php 
        
         
       
        
    
    
    
    } ?>