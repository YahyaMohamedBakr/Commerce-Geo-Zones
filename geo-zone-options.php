<?php 


//Plugin Settings page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cgzones_settings_page');

function cgzones_settings_page($links)
{
   $links[] = '<a href="' . esc_url(admin_url('admin.php?page=cgzones')) . '">' . esc_html__('Settings', 'cgzones') . '</a>';
   return $links;
}

//cgzones in side menu
add_action( 'admin_menu', 'cgzones_menu' );
function cgzones_menu() {
    add_menu_page(
        'Commerce Geo Zones', // page title
        'Commerce Geo Zones', // menu title
        'manage_options', // permisions
        'Commerce-Geo-Zones', // slug
         'cgzones_options_page', // page function
        //  plugin_dir_url( __FILE__ ).'/img/favicon.png',// logo
        //  56 // menu position
    );
}

//main style sheet
add_action('admin_print_styles', 'cgzones_stylesheet');

function cgzones_stylesheet()
{
   wp_enqueue_style('cgzones_style', plugins_url('/css/main.css', __FILE__));
}

  add_action( 'admin_init', 'cgzones_register_settings' );
function cgzones_register_settings() {

    register_setting('cgzones_options_group', 'app_name');
    register_setting('cgzones_options_group', 'sheet_id');
    register_setting('cgzones_options_group', 'credentials_file');
    register_setting('cgzones_options_group', 'cgzones_enable_states');
    register_setting('cgzones_options_group', 'cgzones_enable_cities');
    register_setting('cgzones_options_group', 'cgzones_enable_admin');

    
}




function cgzones_options_page() { ?>
    <div class="wrap">
        <h2>Commerce Geo Zones Settings</h2>
        <form method="post"  action="options.php" >
            <?php settings_fields('cgzones_options_group'); 
             do_settings_sections( 'cgzones_options_group' ); 
          //   wp_nonce_field(); 
             
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
                    <th><label for="cgzones_enable_states">Enable states ?</label></th>
                    <td>
                    <select class="regular-text" name="cgzones_enable_states" id="cgzones_enable_states">
                        <option value ="1" <?php selected( get_option( 'cgzones_enable_states' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'cgzones_enable_states' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cgzones_enable_states">Enable cities ?</label></th>
                    <td>
                    <select class="regular-text" name="cgzones_enable_cities" id="cgzones_enable_cities">
                        <option value ="1" <?php selected( get_option( 'cgzones_enable_cities' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'cgzones_enable_cities' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="cgzones_enable_states">Enable cities in Admin side ?</label></th>
                    <td>
                    <select class="regular-text" name="cgzones_enable_admin" id="cgzones_enable_admin">
                        <option value ="1" <?php selected( get_option( 'cgzones_enable_admin' ), 1 ); ?>>Yes</option>
                        <option value ="0" <?php selected( get_option( 'cgzones_enable_admin' ), 0 ); ?>>No</option>
                    </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </div>
   
        <?php 
    }
    
    ?>




