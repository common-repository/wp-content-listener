<?php
/*
This plugin provides a simple api for using the Google Analytics Measurement Protocol and WordPress.

Google Analytics Measurement Protocol API for WordPress
Copyright (C) 2014, Webhead LLC

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

define ('GAMP_PLUGIN', __FILE__);
define ( 'GAMP_DIR', dirname( GAMP_PLUGIN ) );

define ( 'GAMP_NO_UA_ID', 'UA-XXXXXXXX-X' );

require_once( GAMP_DIR . '/gamp-functions.php');

/**
 * Add a filter to define your google universal analytics id.
 */
function gamp_google_analytics_id() {
    return apply_filters( 'gamp_google_analytics_id', GAMP_NO_UA_ID );
}

/**
 * Convenience function to track a pageview.
 */
function gamp_track_pageview( $args = '' ) {
    $data = wp_parse_args( $args, array(
        't'   => 'pageview',  // The hit type is a 'pageview'
        //'dh'  => '',          // Document hostname. (ex: 'mydemo.com')
        //'dp'  => '',          // Page. (ex: '/home')
        //'dt'   => ''           // Title. (ex: 'homepage')
    ) );
    return gamp_track( $data );
}

/**
 * Convenience function to track an event.
 */
function gamp_track_event( $args ) {
    $data = wp_parse_args( $args, array(
        't'  => 'event',  // The hit type is a 'event'
        'ec' => '',       // Event Category. Required. (ex: 'video')
        'ea' => '',       // Event Action. Required. (ex: 'play')
        // 'el'  => '',       // Event label. (ex: 'holiday')
        // 'ev' => ''        // Event value. (ex: 300)
    ) );
    if ( empty( $data['ec'] ) || empty( $data['ea'] ) ) {
        return new WP_Error( 'gamp_event', 'Google Analytics Event tracking needs an event category and action.' );
    }
    return gamp_track( $data );
}

/**
 * See https://groups.google.com/forum/#!msg/google-analytics-measurement-protocol/rE9otWYDFHw/8JlJJV-UmKcJ
 * a person from Google says cid should be X.Y format.
 *
 * For possible parameters see https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
 *
 * Returns true if ga was contacted.  Apparently whether a hit is recorded or not 200 is returned.
 */
function gamp_track( $data, $user_id = '' ) {
    $uaid = gamp_google_analytics_id();
    if ( empty( $uaid ) || $uaid === GAMP_NO_UA_ID ) {
        return false;
    }
    $payload_data = wp_parse_args( $data, array(
        'v' => 1,
        'tid' => $uaid,
        'cid' => gamp_user_id( $user_id )
    ) );
    $endpoint = 'https://ssl.google-analytics.com/collect?';
    $parameters = http_build_query( $payload_data );
    $result = wp_remote_post( $endpoint . $parameters );
    if ( isset( $result['response'] ) ) {
        return ( $result['code'] == 200 );
    }
    return false;
}

