<?php
/*
Plugin Name: WP-Content Listener
Plugin URI: https://webheadcoder.com/wp-content-listener/
Description: Easy plugin to protect or track file downloads.  Options to restrict files for only logged in users and to track via google analtyics (requires Google Analytics Measurement Protocol API plugin).  For developers, add some PHP code that will run if any files in your wp-content folder is accessed.
Author: Webhead LLC
Version: 0.4
Requires at least: 3.9.0
*/

define( 'WPCL_VERSION', '0.4' );
define( 'WPCL_FILE', __FILE__ );
define( 'WPCL_SLUG', 'wpcl-listen-slug' );

// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

require_once( 'google-analytics-mp-api-for-wordpress/google-analytics-mp-api-for-wordpress.php' );

register_deactivation_hook(__FILE__, 'wpcl_deactivation');

function wpcl_deactivation() {
    //remove htaccess rules
    wpcl_write_htaccess( array() );
}

/**
 * Initialize plugin
 */
function wpcl_init() {
    // used for servers like wpengine where htaccess is not used for redirection.  htacess will not be written to.
    if ( !defined( 'WPCL_SIMPLE_REDIRECT' ) ) {
        define( 'WPCL_SIMPLE_REDIRECT', 0 );
    }
    if (is_admin()) {
        require_once('options-page.php');
    }
    if ( WPCL_SIMPLE_REDIRECT ) {
        if ( function_exists( 'gamp_extract_cid_ajax_setup' ) ) {
            $options = get_option( 'wpcl_options' );
            if ( !empty( $options['track'] ) ) {
                add_action( 'wp_footer', 'gamp_extract_cid_ajax_setup');
            }
        }
    }
    if ( wpcl_is_wpcl_url() ) {
        require_once( 'wp-content-ear.php' );
        wpcl_monitor();
        exit;
    }
}
add_action('init', 'wpcl_init');

/**
 * Return true if the request is part of this plugin.
 */
function wpcl_is_wpcl_url( ) {
    $current_relative_url = add_query_arg();
    $is_wpcl_url = ( strpos( $current_relative_url, wp_make_link_relative( wpcl_url() ) ) === 0 );
    return apply_filters( 'wpcl_is_wpcl_url', $is_wpcl_url );
}

/**
 * The url files will be redirected to.
 */
function wpcl_url() {
    return apply_filters( 'wpcl_url', trailingslashit( get_home_url( null, WPCL_SLUG ) ) );
}

/**
 * Since it's not recommended to use the WP_CONTENT_DIR, have a fallback way to get it.
 */
function wpcl_get_wp_content_directory() {
    $wp_content_directory = '';
    if ( !defined( 'WP_CONTENT_DIR' ) ) {
        $wp_content_directory = ABSPATH . 'wp-content';
    }
    else {
        $wp_content_directory = WP_CONTENT_DIR;
    }
    $directory = apply_filters( 'wpcl_wp_content_directory', $wp_content_directory );
    // $directory should not be the wp root dir.
    if ( realpath($directory) == realpath(ABSPATH) ) {
        $directory = $wp_content_directory;
    }
    return $directory;
}

/**
 * Return an array of subdirectories in wp-content.
 */
function wpcl_get_wp_content_subdirectories() {
    return wpcl_get_all_subdirectories( wpcl_get_wp_content_directory() );
}

/**
 * Takes in the full path to make relative (path_to_make_relative) and the path this should be relative to.
 * Similar to wp_make_link_relative, returns with a leading slash.
 */
function wpcl_make_path_relative( $path_to_make_relative, $relative_to_full_path = '' ) {
    if ( empty( $relative_to_full_path ) ) {
        $relative_to_full_path = ABSPATH;
    }
    $relative_to_full_path = untrailingslashit( $relative_to_full_path );
    return str_replace( $relative_to_full_path, '', $path_to_make_relative );
}

/**
 * Write the htaccess files for the chosen directories.
 */
function wpcl_write_htaccess( $directories ) {
    $mime_types = wpcl_supported_mimetypes();
    $extensions = array_keys( $mime_types );
    $wpcl_url = wpcl_url();
    if ( wpcl_on_iis() ) {
        //rewrite not working with url, do redirect for now.
        $CONTENT_TEMPLATE = '
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="WP Content Listener rules" stopProcessing="true">
                    <match url="^(.*\.(' . implode( '|', $extensions ) . '))$" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" />
                    </conditions>
                    <action type="Redirect" url="' . $wpcl_url . '?f={R:0}" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
';
        $start_content = "\n<!-- BEGIN WPCI -->";
        $end_content = "<!-- END WPCI -->\n";      
    }
    else {
        $CONTENT_TEMPLATE = "

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteCond %{REQUEST_FILENAME} -f
  RewriteRule ^(.*\.(" . implode( '|', $extensions ) . "))$ " . $wpcl_url . "?f=$1 [L]
</IfModule>

";
        $start_content = "\n### BEGIN WPCI ###";
        $end_content = "### END WPCI ###\n";        
    }

    foreach ( $directories as $directory ) {
        //write .htaccess
        $matches = array();
        if ( wpcl_on_iis() ) {
            $file = trailingslashit( $directory ) . 'web.config';
        }
        else {
            $file = trailingslashit( $directory ) . '.htaccess';
        }
        $current = '';
        if ( file_exists( $file ) ) {
            $current = file_get_contents($file);
            if ( $current === FALSE ) {
                $current = '';
            }
        }
        preg_match( '/('.$start_content.')(.*)('.$end_content.')/si', $current, $matches );
        $new_content = $start_content . $CONTENT_TEMPLATE . $end_content;
        //clean it first
        if ( !empty( $matches ) ) {
            $current = preg_replace('/('.$start_content.')(.*)('.$end_content.')/si', '', $current );
        }
        $current .= $new_content;
        // Write the contents back to the file
        if ( wpcl_is_writable( $directory ) ) {
            file_put_contents($file, $current);
        }
    }

    //remove previous directories
    $options = get_option('wpcl_options');
    $old_directories = isset( $options['directories'] ) ? $options['directories'] : array();
    $removed_directories = array_diff( $old_directories, $directories );
    foreach( $removed_directories as $directory ) {
        $matches = array();
        $file = trailingslashit( $directory ) . '.htaccess';
        if ( !file_exists( $file ) ) {
            continue;
        }
        $current = file_get_contents($file);
        if ( $current === FALSE ) {
            //no content
            continue;
        }
        preg_match( '/('.$start_content.')(.*)('.$end_content.')/si', $current, $matches );
        if ( !empty( $matches ) ) {
            $current = preg_replace('/('.$start_content.')(.*)('.$end_content.')/si', '', $current );
            if ( empty( $current ) ) {
                //only had our stuff, so remove the file.
                unlink( $file);
            }
            else {
                // Write the contents back to the file
                if ( wpcl_is_writable( $file ) ) {
                    file_put_contents($file, $current);      
                }
                else {
                    //add error
                }
            }
        }
    }

}

/**
 * Return true if we are on IIS.
 */
function wpcl_on_iis() {
    $sSoftware = strtolower( $_SERVER["SERVER_SOFTWARE"] );
    if ( strpos($sSoftware, "microsoft-iis") !== false )
        return true;
    else
        return false;
}

/**
 * Return true if the file or directory is writable.
 */
function wpcl_is_writable( $path, $filename = '.htaccess' ) {
    return ( ( ( ! file_exists( $path . $filename ) && is_writable( $path ) ) || is_writable( $path . $filename ) ) ) && WPCL_SIMPLE_REDIRECT == 0;
}

/**
 * Return if the user is logged in if the protect option is selected.
 */
function wpcl_protect( $has_access ) {
    $options = get_option( 'wpcl_options' );
    if ( !empty( $options['protect'] ) ) {
        $has_access = is_user_logged_in();
    }
    return $has_access;
}
add_filter( 'wpcl_access_filter', 'wpcl_protect', 1 );


/**
 * Track the user using the Google Analytics Measurement Protocol WP API plugin.
 */
function wpcl_track( $filename, $url ) {
    if ( !function_exists( 'gamp_track_event' ) )
        return;
    $options = get_option( 'wpcl_options' );
    if ( !empty( $options['track'] ) ) {
        $relative_file_name = wpcl_make_path_relative( $filename );
        $params = apply_filters( 'wpcl_track_ga_parameters', array(
                'ec' => 'Files',              // Event Category. Required. (ex: 'video')
                'ea' => 'Download',           // Event Action. Required. (ex: 'play')
                'el'  => $relative_file_name  // Event label. (ex: 'holiday')
        ) );
        gamp_track_event( $params );
    }
}
add_action( 'wpcl_before_file_download', 'wpcl_track', 10, 2 );

/**
 * Return the saved google analtyics id.
 */
function wpcl_google_analytics_id( $ga_id ) {
    $options = get_option( 'wpcl_options' );
    if ( !empty( $options['ga_id'] ) ) {
        $ga_id = $options['ga_id'];
    }
    return $ga_id;
}
add_filter('gamp_google_analytics_id', 'wpcl_google_analytics_id');



/**
 * List of supported mimetypes.
 */
function wpcl_supported_mimetypes() {
    return apply_filters( 'wpcl_supported_mime_types', wp_get_mime_types() );
}