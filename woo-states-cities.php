
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




require (ABSPATH. '/vendor/autoload.php');



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
$range = 'Sheet1'; // Sheet name

$valueRange= new Google_Service_Sheets_ValueRange();
//$valueRange->setValues(["values" => ["a", "j"]]); // values for each cell


$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();
$States_values = $response['values'][0];


// Replace states
 add_filter( 'woocommerce_states', 'woo_custom_woocommerce_states' );
 function woo_custom_woocommerce_states( $states ) {
    global $States_values;
    if ( !( $States_values )) {
        $states['EG'] = array(
            '0' => 'يتعذر تحميل المحافظات يرجى المحاولة لاحقا'
        );
        return $states;

    }else{
    
   echo $states;
    $states['EG'] = array();
    foreach ($States_values as $state_key=>$state_value) {
        $states['EG'][$state_key] = $state_value ;  
    }
    return $states;
    }   
}
// ...

// ...

// $yahya = $get_co_and_row();
// // echo $yahya;

// function get_co_and_row() {
//     global $values;
    
//     if (empty($values)) {
//         echo 'No data found.';
//     } else {
//         $columns = array(); // Array to store column values
//         $rows = array(); // Array to store row values
        
//         $header = array_shift($values); // Remove the header row from values
        
//         // Loop through columns
//         for ($columnIndex = 0; $columnIndex < count($values); $columnIndex++) {
//             $columnData = array();
            
//             // Loop through rows in the column
//             foreach ($values as $rowIndex => $row) {
//                 //if (isset($row[$columnIndex]) && !empty($row[$columnIndex])) {
//                 $cellValue = $row[$columnIndex];
//                 $columnData[] = $cellValue;
//                 //}
//             }
            
//             // Set the column data in the columns array
//             if (!empty($columnData)) {
//                 $columns[] = $columnData;
//             }
//         }
        
//         // Loop through rows
//         foreach ($values as $row) {
//             $rowData = array();
            
//             // Loop through cells in the row
//             foreach ($row as $cellValue) {
//                 $rowData[] = $cellValue;
//             }
            
//             // Set the row data in the rows array
//             $rows[] = $rowData;
//         }
        
//         // Return the resulting arrays
//         return array(
//             'columns' => $columns,
//             'rows' => $rows
//         );
//     }
// }

// $result = get_co_and_row();
// $columns = $result['columns'];
// $rows = $result['rows'];

