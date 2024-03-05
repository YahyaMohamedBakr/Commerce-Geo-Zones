<?php 


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
//    register_setting('cgz_options_group', 'cgz_options_nonce');

    
}

// if (isset($_POST["credentials_file"]) && wp_verify_nonce($_POST['cgz_options_nonce'], 'save_cgz_options')) {
    
    
//     update_option('credentials_file', $_POST['credentials_file']);
// }


function cgz_options_page() { ?>
    <div class="wrap">
        <h2>Commerce Geo Zones Settings</h2>
        <form method="post"  action="options.php" >
            <?php settings_fields('cgz_options_group'); 
             do_settings_sections( 'cgz_options_group' ); 
            wp_nonce_field(); 
           
             
             ?>
            <table class="form-table">
                <tr>
                    <th><label for="app_name">Google Application Name:</label></th>
                    <td>
                        <input  type = 'text' class="regular-text" id="app_name" name="app_name" value="<?php echo  esc_attr(get_option('app_name')); ?>" style="<?php echo empty(get_option('app_name')) ? 'border: 1px solid red' : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="sheet_id">Google Sheet Id:</label></th>
                    <td>
                        <input  type = 'text' class="regular-text" id="sheet_id" name="sheet_id" value="<?php echo  esc_attr(get_option('sheet_id')); ?>" style="<?php echo empty(get_option('sheet_id')) ? 'border: 1px solid red' : ''; ?>">
                    </td>
                </tr>
                
                <tr>
                <th><label for="credentials_file">Google credentials file:</label></th>
                    <td>
                        <textarea class="regular-text" name="credentials_file" id="credentials_file" style ="height:150px; <?php echo empty(get_option('credentials_file')) ? 'border: 1px solid red' : ''; ?>">
                            <?php echo  esc_attr(get_option('credentials_file')); ?>
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
       <script>
//         jQuery(document).ready(function($) {
//     $('#upload_button').on('click', function() {
//         var file_frame = wp.media.frames.file_frame = wp.media({
//             title: 'Select Google credentials file',
//             button: {
//                 text: 'Use this file'
//             },
//             multiple: false
//         });

//         file_frame.on('select', function() {
//             var attachment = file_frame.state().get('selection').first().toJSON();
//             $('#credentials_file').val(attachment.url);
//             $('#uploaded_file').text(attachment.url);
//         });

//         file_frame.open();
//     });
// });

       </script>
        <?php 
    }
    
    ?>




