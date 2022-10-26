<?php
/**
* @package REXpressPlugin
* 
* Plugin Name: Retail Express X WooCommerce
* Description: Import products from Retail Express to WooCommerce.
* Version: 0.1
* Author: Fligno PH
* Author URI: fligno.com
* License: GPLv2 or later
* Text Domain: retail-express-x-woocommerce
**/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );


// Require once the autoload
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
    require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}


/**
 * Will run during activation
 */
function activate_rexpress_plugin() {
    Inc\Base\Activate::activate();
}
register_activation_hook(__FILE__, 'activate_rexpress_plugin' );

/**
 * Will run during deactivation
 */
function deactivate_rexpress_plugin() {
    Inc\Base\Deactivate::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_rexpress_plugin' );

/**
 * Init all the core classess of the plugin
 */
if ( class_exists( 'Inc\\Init' ) ) {
    Inc\Init::register_services();
}