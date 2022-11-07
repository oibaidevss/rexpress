<?php

/**
 * Trigger this file on uninstall
 * 
 * @package REXpressPlugin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ){
    die;
}

delete_option( 'rex__api_url' );
delete_option( 'rex__api_api' );
delete_option( 'rex__admin_email' );
delete_option( 'rex__retail_express' );