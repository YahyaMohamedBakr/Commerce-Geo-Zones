<?php  

interface Cgzones_changeCity {

    function cityDropdown( $fields );
    function addScript();


}

class Cgzones_client implements Cgzones_changeCity{
     protected $fieldType;
     protected $nonce;
     function __construct($fieldType, $nonce){
        $this->fieldType= $fieldType;
        $this->nonce=$nonce;
     }
   function cityDropdown( $fields ){
        $option_cities = array(
       
            "0"=> "حدد خياراً"
         );
   $fields [$this->fieldType]['type'] = 'select';
   $fields [$this->fieldType]['options'] =  $option_cities;
    return $fields;


    }
   public function addScript(){

        $current_url = $_SERVER['REQUEST_URI'];
        if (( is_checkout() && ! is_wc_endpoint_url() )||strpos($current_url, '/billing') !== false || strpos($current_url, '/shipping') !== false) {
            $woo=WC(); //woocommerce object
            $selected_billing_city= $woo->customer->get_billing_city();
            $selected_shipping_city = $woo->customer->get_shipping_city();
            
            wp_enqueue_script('custom-client-script', plugin_dir_url( __FILE__ ) . '/js/client.js', array('jquery'), '1.0', true);
            wp_localize_script( 'custom-client-script', 'custom_client_script_vars', array(
    
            //pass values to the script file
                'site_url' => get_site_url(),
                'selected _city'=>  $selected_billing_city,
                'selected_shipping_city'=> $selected_shipping_city ,
                'nonce'=> $this->nonce
                
    
            ));
        }
    }
}


class Cgzones_admin extends Cgzones_client{

    function cityDropdown( $fields ){
        $order = wc_get_order();
        $selected  = $order->get_billing_city();
        if($this->fieldType=='shipping_city'){
            $selected  = $order->get_shipping_city();
        }
        
        $selected_city =explode(':',$selected ) ;
        $selected_city_name =$selected_city[1];
        $selected_city_value = $selected_city[0];
        
        if( !$selected_city_name || !$selected_city_value){
            $option_cities = array(
            
                '0' => 'اختر مدينة'
            );
        }else{
            $option_cities = array(
                $selected_city_value.':'.$selected_city_name => $selected_city_name,
                '0'=>'جارٍ تحميل بقية المدن'
            );
        }
    
        
         // Set billing city field as select dropdown
         $fields['city']['type'] = 'select';
         $fields['city']['options'] = $option_cities;
     
         return $fields;

    }


    function addScript(){
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
    
        wp_enqueue_script( 'cgzones-admin-side-script', plugin_dir_url( __FILE__ ) . '/js/admin.js', array( 'jquery' ), '1.0', true );
        wp_localize_script( 'cgzones-admin-side-script', 'cgzones_admin_side_script_vars', array(
            //pass values to the script file
            'site_url' => get_site_url(),
            'selected_billing_city_name'=>$selected_billing_city_name,
            'selected_billing_city_value'=>$selected_billing_city_value,
            'selected_shipping_city_name'=> $selected_shipping_city_name,
            'selected_shipping_city_value'=> $selected_shipping_city_value,
            'selected_billing_state_value'=> $selected_billing_state_value,
            'selected_shipping_state_value'=> $selected_shipping_state_value,
            'nonce'=> $this->nonce
        ));
    }
    }


}