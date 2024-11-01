<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');
    
/**
 * Return the ga cid. (or what will be called user_id)
 */
function gamp_user_id( $user_id = '' ) {
    $gamp_cid = '';
    if ( !empty( $user_id ) ) {
        $gamp_cid = get_user_meta( $user_id, 'gamp_cid', true );   
    }
    if ( empty( $gamp_cid ) ) { 
        $gamp_cid = gamp_extract_cid();
    }
    gamp_save_cid( $user_id, $gamp_cid );
    return $gamp_cid;
}

/**
 * Save the cid to the user so we can use it when js is not available.
 */
function gamp_save_cid( $user_id = '', $gamp_cid = '' ) {
    if ( !empty( $user_id ) && !empty( $gamp_cid ) ) {
        update_user_meta( $user_id, 'gamp_cid', $gamp_cid );
    }
}

/**
 * Get the google analytics UUID.
 */
function gamp_extract_cid( $ga = '' ) {
    $gamp_cid = '';
    if ( empty( $ga ) ) {
        $ga = isset( $_COOKIE['_ga'] ) ? $_COOKIE['_ga'] : '';
    }
    if ( !empty( $ga ) ) {
        list($version,$domainDepth, $cid, $ts) = preg_split('/[\.]/', $_COOKIE["_ga"], 4);
        // according to 
        // http://www.stumiller.me/implementing-google-analytics-measurement-protocol-in-php-and-wordpress/
        // cid is both cid and ts.
        $contents = array('version' => $version, 'domainDepth' => $domainDepth, 'cid' => $cid . '.' . $ts);

        //3rd spot is the cid according to multiple sources
        $gamp_cid = $contents['cid'];
    }
    return $gamp_cid;
}
add_filter( 'init', 'gamp_extract_cid' );


/**
 * Use if server does not support reading cookies via PHP.  Requires jQuery.
 * By default does not run. add the following code to your theme to run:
 * add_action( 'wp_footer', 'gamp_extract_cid_ajax_setup');
 */
function gamp_extract_cid_ajax_setup() { ?>
    <script type="text/javascript">
    if (typeof jQuery != 'undefined') { 
        function gamp_get_cookie() {
           cookiearray  = document.cookie.split(';');
           for(var i = 0; i < cookiearray.length; i++){
              name = cookiearray[i].split('=')[0];
              value = cookiearray[i].split('=')[1];
              if ( name == '_ga' ) {
                return value;
              }
           }
        }
        var gamp_ga = gamp_get_cookie();
        if ( gamp_ga != '' ) {
            jQuery.post( "<?php echo admin_url('admin-ajax.php'); ?>", {
                action: 'gamp-extract-cid',
                ga: gamp_ga,
            }, function(){}, 'json')
        }
    }
    </script>
<?php
}

function gamp_extract_cid_ajax() {
    if ( is_user_logged_in() && isset( $_POST['ga'] ) ) {
        $gamp_cid = gamp_extract_cid( $_POST['ga'] );
        gamp_save_cid( get_current_user_id() , $gamp_cid );
    }
    exit;
}
add_action('wp_ajax_gamp-extract-cid', 'gamp_extract_cid_ajax');






