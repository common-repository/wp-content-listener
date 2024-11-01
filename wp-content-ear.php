<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

/**
 * Deterimines if the folder is in our list to listen to.  if so, do the action.
 */
function wpcl_monitor() {
    $dirs = wpcl_paths_to_monitor();
    foreach ( $dirs as $dir ) {
        $path_to_file = WPCL_SIMPLE_REDIRECT == 1 ? $_SERVER['QUERY_STRING'] : $_GET['f'];
        $filename = realpath( $dir . $path_to_file );
        if ( strpos( $filename, realpath( $dir ) ) === 0 ) {
            if ( file_exists( $filename ) ) {
                $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                if (apply_filters('wpcl_access_filter', true, $filename, $url)) {
                    do_action( 'wpcl_before_file_download', $filename, $url );
                    wpcl_get_file($filename);
                }
                else {
                    do_action( 'wpcl_no_access', $filename, $url );
                    global $wp_query;
                    $wp_query->set_404();
                    status_header(404);
                    include( get_query_template( '404' ) );
                    exit;
                }
            }
        }
    }
    do_action( 'wpcl_not_monitoring', $filename, $url );
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    include( get_query_template( '404' ) );
    exit;
}

/**
 * By default any images attached to a post with status 'publish' will be public.
 */
function wpcl_public_exception( $has_access, $file_name, $url ) {
    //user already has access or filtered to skip this
    if ( $has_access || apply_filters( 'wpcl_skip_public_exception', false ) ) {
        return $has_access;
    }

    //take out any -100x100 in file name.
    $pattern = '/(-[0-9]+x[0-9]+)\.(jpg|jpeg|png|gif)$/i';
    $replacement = '.\2';
    $main_url = preg_replace($pattern, $replacement, $url);

    global $wpdb;
    $query = '
    select p.ID  
      from ' . $wpdb->posts . ' as p
      inner join ' . $wpdb->posts . ' as p2 
        on (p.ID = p2.post_parent)
        and (p2.guid = \'%s\')
        and (p2.post_type = \'attachment\')
      where (p.post_status = \'publish\')
      limit 1;';
    $results = $wpdb->get_results( $wpdb->prepare( $query, $main_url ) );

    return ( count( $results ) == 1 );
}
add_filter('wpcl_access_filter', 'wpcl_public_exception', 5, 3);

/**
 * Return the paths to monitor.  The uploads path is included by default.
 */
function wpcl_paths_to_monitor() {
    $options = get_option('wpcl_options');
    $directories = array();
    if ( !empty( $options['directories'] ) ) {
        $directories = $options['directories'];
    }
    return apply_filters( 'wpcl_paths_to_monitor', $directories );
}

/**
 * Taken from privatefiles.php wordpress plugin.
 * Basically this is what shows a file.
 */
function wpcl_get_file($filename) {
    //This section of code is modified from evDbFiles (http://virtima.pl/evdbfiles)
    $file_time = filemtime($filename);
    
    $send_304 = false;
    if (php_sapi_name() == 'apache') {
        // if our web server is apache
        // we get check HTTP
        // If-Modified-Since header
        // and do not send image
        // if there is a cached version
        $ar = apache_request_headers();
            if (isset($ar['If-Modified-Since']) && // If-Modified-Since should exists
            ($ar['If-Modified-Since'] != '') && // not empty
            (strtotime($ar['If-Modified-Since']) >= $file_time)) { // and grater than file_time
            $send_304 = true;
        }
    }

    if ($send_304) {
        // Sending 304 response to browser
        // "Browser, your cached version of image is OK
        // we're not sending anything new to you"
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file_time).' GMT', true, 304);
    } else {
        // outputing Last-Modified header
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file_time).' GMT', true, 200);

        // Set expiration time +1 year
        // We do not have any photo re-uploading
        // so, browser may cache this photo for quite a long time
        header('Expires: '.gmdate('D, d M Y H:i:s',  $file_time + 86400*365).' GMT', true, 200);
        
        // outputing HTTP headers
        header('Content-Length: '.filesize($filename));

        //Not all php setups support this, eg. dreamhost
        //$finfo = finfo_open(FILEINFO_MIME);
        //$ftype = finfo_file($finfo, $filename);
        //finfo_close($finfo);
        //$ftype = mime_content_type($filename);

        $ftype = wpcl_mime_type($filename);
        if ( empty( $ftype ) ) {
            exit;
        }
        header("Content-type: " . $ftype);
        
        //$isImage = strpos($ftype,'image/') != '';
        //if (!$isImage){
        //  header('Content-Disposition: attachment; filename="'.$_SERVER['REQUEST_URI'].'"');
        //  header('Content-Transfer-Encoding: binary');
        //}
        ob_clean();
        flush();
        readfile($filename);
        exit;
    }
}

function wpcl_mime_type($filename) {
    $extension = wpcl_get_extension($filename);
    if ( ! $extension )
        return false;

    $mime_types = wpcl_supported_mimetypes();
    $extensions = array_keys( $mime_types );

    foreach( $extensions as $_extension ) {
        if ( preg_match( "/{$extension}/i", $_extension ) ) {
            return $mime_types[$_extension];
        }
    }

    return '';
}

function wpcl_get_extension($filename) {
    $start = strrpos($filename,'/');
    if ($start == '') {
        //no / found in file name, not in a folder
        $start = 0;
    }
    $justfile = substr($filename,$start);
    $pos = strrpos($justfile,'.');
    return ($pos != '' ? substr($justfile, $pos+1) : '');
}
