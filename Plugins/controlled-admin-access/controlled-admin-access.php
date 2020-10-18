<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpruby.com
 * @since             1.0.0
 * @package           Controlled_Admin_Access
 *
 * @wordpress-plugin
 * Plugin Name:       Controlled Admin Access
 * Plugin URI:        https://wpruby.com/product/controlled-admin-access
 * Description:       Grant a temporary limited admin access to others.
 * Version:           1.3.9
 * Author:            WPRuby
 * Author URI:        https://wpruby.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       controlled-admin-access
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define('CAA_VERSION', '1.3.9');
define('CAA_DIR', plugin_dir_path( __FILE__ ));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-controlled-admin-access-activator.php
 */
function activate_controlled_admin_access() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-controlled-admin-access-activator.php';
	Controlled_Admin_Access_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-controlled-admin-access-deactivator.php
 */
function deactivate_controlled_admin_access() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-controlled-admin-access-deactivator.php';
	Controlled_Admin_Access_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_controlled_admin_access' );
register_deactivation_hook( __FILE__, 'deactivate_controlled_admin_access' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-controlled-admin-access.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_controlled_admin_access() {

	$plugin = new Controlled_Admin_Access();
	$plugin->run();

}
run_controlled_admin_access();


