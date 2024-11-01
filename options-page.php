<?php
/*********************************
 * Options page
 *********************************/

// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

/**
 *  Add menu page
 */
function wpcl_options_add_page() {
    $wpcl_hook = add_options_page( 'WP-Content Listener', // Page title
                      'WP-Content Listener', // Label in sub-menu
                      'manage_options', // capability
                      'wpcl-options', // page identifier 
                      'wpcl_options_do_page' ); // call back function name
                      
    add_action( "admin_enqueue_scripts-" . $wpcl_hook, 'wpcl_admin_scripts' );
}
add_action('admin_menu', 'wpcl_options_add_page');

/**
 * Init plugin options to white list our options
 */
function wpcl_options_init(){
    global $plugins_dir_name;
    $plugins_dir_name = basename( dirname( plugin_dir_path( __FILE__ ) ) );
    register_setting( 'wpcl_options_options', 'wpcl_options', 'wpcl_options_validate' );
    if ( WPCL_SIMPLE_REDIRECT && isset( $_GET['page'] ) && $_GET['page'] == 'wpcl-options' ) {
        add_action('admin_notices', 'wpcl_admin_notice' );
    }
}
add_action('admin_init', 'wpcl_options_init' );

function wpcl_admin_notice() {
    echo '<div class="update-nag" id="messages"><p>WPCL_SIMPLE_REDIRECT is set.  This means the .htaccess file will not be written.  Redirect rules will need to be manually set on the server to redirect to ' . wpcl_url() . '?<i>protectedFilename</i>' . '. </p>';
    $options = get_option('wpcl_options');
    if ( !empty( $options['directories'] ) && is_array( $options['directories'] ) ) {
        $first_dir = current( $options['directories'] );
        $abspath = untrailingslashit( wpcl_get_wp_content_directory() );
        $relative_url = str_replace( $abspath, '', $first_dir );
        echo '<p>For example, at WPEngine on the Redirect rules screen, the Source regex would be: <br><strong>' . trailingslashit( $relative_url ) .  '(.*)</strong></p>';
        echo '<p>and the Destination would be: <br><strong>' . wpcl_url() . '?$1' . '</strong></p>';
        echo '<p>You would need a rule for each selected directory or use some fancy regex to catch everything.</p>';
    }
    echo '</div>';
}


/**
 * Recursive function to store all subdirectories of $directory.
 * originally from http://pastebin.com/qvyF1VWX
 */
function wpcl_get_all_subdirectories( $directory ) {
    global $plugins_dir_name;
    $dirs = array_map( 'trailingslashit', preg_grep( '#/' .$plugins_dir_name . '|mu-plugins#', glob( $directory . '*', GLOB_ONLYDIR ), PREG_GREP_INVERT ) );
    //http://stackoverflow.com/questions/1877524/does-glob-have-negation

    $dir_array = array_fill_keys( $dirs, array() );
    foreach( $dir_array as $dir => $subdirs ) {
        $dir_array[$dir] = array_merge( $subdirs, wpcl_get_all_subdirectories( $dir ) );
    }
    return $dir_array;
}

/**
 * Recursive function to go through the array of directories/subdirectories.
 */
function wpcl_parse_subdirectory_array( $options, $dirs, $depth = 0 ) {
    $depth++;
    $abspath = untrailingslashit( wpcl_get_wp_content_directory() );
    foreach ( $dirs as $dir => $subdirs ) {
        $subdirs_class = 'folder-has-children';
        if ( count($subdirs) == 0 ) {
            $subdirs_class = '';
        }
        echo '<div class="folder folder-depth-' . $depth . ' ' . $subdirs_class . '">';
        if ( $depth != 1 ) {
            $dir_display = str_replace( $abspath, '', $dir );
            $is_checked = '';
            if ( isset( $options['directories'] ) ) {
                $is_checked = in_array( esc_attr( $dir ), $options['directories'] ) ? 'checked="checked"' : '';
            }
            echo '<span class="wpcl-control-container"><span class="spacer"></span></span>';
            echo '<input id="' . esc_attr( $dir_display ) . '" class="wpcl-checkbox" type="checkbox" name="wpcl_options[directories][]" value="' . esc_attr( $dir ) . '" ' . $is_checked . '>';
            echo '<label for="' . esc_attr( $dir_display ) . '">' . $dir_display . '</label>';
        }
        else {
            echo '/' . trailingslashit( str_replace( ABSPATH, '', wpcl_get_wp_content_directory() ) );
        }
        $path = wpcl_parse_subdirectory_array( $options, $subdirs, $depth );
    }
    if ( $depth != 1 ) {
       echo '</div> <!-- .folder -->'; 
    }
    return $path;
}

/**
 * Draw the menu page itself
 */
function wpcl_options_do_page() {
    if ( !current_user_can( 'manage_options' ) ) { 
     wp_die( __( 'You do not have sufficient permissions to access this page.' ) ); 
    } 
    ?>
    <div class="wrap">

            <div class="wpcl-header">
                <div class="wpcl-description">
                <h2>WP-Content Listener</h2>
                    <p class="intro">
                        WP-Content Listener is a plugin that lets you protect and track specific directories within the wp-content folder.
                    </p>
                </div>
                <div class="wpcl-donate">
                    <p>If this plugin has helped you, please consider giving back.</p>
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                    <input type="hidden" name="cmd" value="_s-xclick">
                    <input type="hidden" name="hosted_button_id" value="J6GP6AUVJF9RE">
                    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                    <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                    </form>
                </div>
            </div>
            <div class="clear"></div>
            <hr>
            <form method="post" action="options.php">
                <?php settings_fields('wpcl_options_options'); ?>
                <?php $options = get_option('wpcl_options'); ?>
            <p>
                <input id="wpcl_options[protect]" type="checkbox" name="wpcl_options[protect]" value="1" <?php checked( wpcl_option('protect', '', $options), 1 ); ?>>
                <label class="wpcl-option-label" for="wpcl_options[protect]">Protect selected directories</label>
            </p>
            <p class="desc">Only logged in users can access the selected directories.<br>(<strong>NOTE:</strong> if a file is included/attached in a published post it will remain public.)
            </p>
            <hr>
            <p>
                <input id="wpcl_options[track]" type="checkbox" name="wpcl_options[track]" value="1" <?php echo !wpcl_is_gamp_active() ? 'disabled="disabled"' : '' ?> <?php checked( wpcl_option('track', '', $options), 1 ); ?>>
                <label class="wpcl-option-label" for="wpcl_options[track]"> Enable tracking </label><br>
                <span class="desc">(For best results, server should support reading cookies with PHP)</span>
            </p>
            <p class="desc">
                <label>Google Universal Analytics ID</label>
                <br><input type="text" name="wpcl_options[ga_id]" value="<?php echo wpcl_option('ga_id', '', $options);?>" <?php echo !wpcl_is_gamp_active() ? 'disabled="disabled"' : '' ?>>
            </p>
            <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save All') ?>" />
            </p>
            <hr>
            <h3>Directories to Watch</h3>        
            <div class="directories-list">
            <?php
                $dirs = wpcl_get_wp_content_subdirectories();
                wpcl_parse_subdirectory_array( $options, $dirs );
            ?>
            </div> <!-- .directories-list -->
            <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save All') ?>" />
            </p>
        </form>
    </div>
    <?php   
}

/**
 * Sanitize and validate input. Accepts an array, return a sanitized array.
 */
function wpcl_options_validate($input) {
    global $wp_settings_errors;

    if ( !empty( $input['track'] ) ) {
        if ( empty( $input['ga_id'] ) ) {
            add_settings_error(
                'ga-id',
                'ga_id',
                __( 'Please enter a valid Google Universal Analtyics ID.', 'whpm' )
            );
        }
    }

    if ( empty( $wp_settings_errors ) ) {
        $abspath = untrailingslashit( ABSPATH );
        //add .htacces files to all directories        
        $directories = isset( $input['directories'] ) ? $input['directories'] : array();
        foreach( $directories as $directory ) {
            if ( !wpcl_is_writable( $directory ) && !WPCL_SIMPLE_REDIRECT ) {
                $dir_display = str_replace( $abspath, '', $directory );
                add_settings_error(
                    'wpcl-htaccess-writeable',
                    esc_attr( $directory ),
                    'Your .htaccess file in ' . $dir_display . ' is not writable.  Currently this plugin cannot work without this file being writable.'
                );
            }
        }
        if ( empty( $wp_settings_errors ) ) {
            wpcl_write_htaccess( $directories );
        }
    }
    return $input;
}


/**
 * Enqueue Scripts
 */
function wpcl_admin_scripts() {
    do_action ('wpcl_admin_scripts');
}

/**
 * Enqueue scripts for the admin side.
 */
function wpcl_enqueue_scripts($hook) {
    if( 'settings_page_wpcl-options' != $hook )
        return;
    wp_enqueue_script( 'wpcl-options',
        plugins_url( '/js/options.js', WPCL_FILE ),
        array( 'jquery' ),
        WPCL_VERSION );
    wp_enqueue_style( 'wpcl-options',
        plugins_url( '/css/options.css', WPCL_FILE ),
        array( ),
        WPCL_VERSION );
    wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );

}
add_action( 'admin_enqueue_scripts', 'wpcl_enqueue_scripts' );

/**
 * Return true if Google Analytics Measurement Protocol WP API is active.
 */
function wpcl_is_gamp_active() {
    //gamp is part of this plugin for now.
    return true;
}


/**
 * Get option
 */
function wpcl_option($name, $default='', $options = false) {
    if (empty($options)) {
        $options = get_option('wpcl_options'); 
    }

    if (!empty($options) && !empty($options[$name])) {
        $ret = $options[$name];
    }
    else {
        $ret = $default;
    }
    return $ret;
}
