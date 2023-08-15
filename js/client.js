// client-side.js

function fillCities(select, options) {
    for(var option in options ) {
        // console.log (options[option]);
        
            select.appendChild(new Option(options[option] , options[option])); 
        
    }

}


var siteUrl = custom_client_script_vars.site_url;
var selectBillingCity = custom_client_script_vars.selected_billing_city;
var selectShippingCity = custom_client_script_vars.selected_shipping_city;




function getAreas(dropdownElement, stateId, defaultValue) {
    // console.log(stateId);
    if (!dropdownElement) {
        return;
    }
   if (!stateId) {
       dropdownElement.replaceChildren();
       return;
       
   }

    //reset select element
    dropdownElement.replaceChildren();
   
    // waiting message until the data arrives 
    dropdownElement.appendChild(new Option('جارٍ تحميل المدن الخاصة بهذه المحافظة.......', '0'));

    fetch(siteUrl+"/wp-json/wgz/getareas?id="+(stateId-1))
    .then((response) => response.json())

    
    .then((data) => {
        console.log('data is')
        console.log(data)
        dropdownElement.replaceChildren();
        fillCities(dropdownElement, data);
        if (defaultValue) dropdownElement.value = defaultValue;
    //    console.log('Sucsess')
    //    console.log(data)
    }) .catch((error) => {
        dropdownElement.replaceChildren();
        dropdownElement.appendChild(new Option('خطأ في الخوادم يرجى المحاولة لاحقاً', '0'));
        console.error('Error:', error);
    });

}

//  window.onload = function()  {

    console.log ('Billing City is  '+selectBillingCity);
    console.log ('Shipping City is  '+selectShippingCity);


    
    var billing_state = document.querySelector('select#billing_state');
    if (billing_state) {
        billing_state.onchange = function() {
            getAreas( document.querySelector('select#billing_city') ,billing_state.value, selectBillingCity );
        };
    }

    var shipping_state = document.querySelector('select#shipping_state');
    if (shipping_state) {
        shipping_state.onchange = function() {
            getAreas( document.querySelector('select#shipping_city'), shipping_state.value, selectShippingCity);
        };
    }
//  };



jQuery(document).ready(function($) {
    $('select#billing_city, select#shipping_city').select2({
        placeholder: {
            id: '', // the value of the option
            text: 'اختر مدينة'
        },
        allowClear: true,
        
    });
});