<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ecomhub.com/
 * @since             1.0.0
 * @package           Ecomhub_FI
 *
 * @wordpress-plugin
 * Plugin Name:       Ecomhub's Funnel Integration
 * Plugin URI:        https://ecomhub.com/
 * Description:       Processes messages from ClickFunnels. Once a message is received, the user is found, the purchase is tied to WooCommerce; and other plugins are notified
 * Version:           1.0.1
 * Author:            Will Woodlief
 * Author URI:        mailto:willwoodlief@gmail.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ecomhub-fi
 * Domain Path:       /languages
 * Requires at least: 4.6
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ECOMHUB_FI_PLUGIN_NAME_VERSION', '1.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ecomhub-fi-activator.php
 */
function activate_ecomhub_fi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ecomhub-fi-activator.php';
	Ecomhub_Fi_Activator::activate();
}

function ecomhub_fi_update_db_check() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ecomhub-fi-activator.php';
    $version = get_site_option( '_ecombhub_fi_db_version' );
    if ( $version != Ecomhub_Fi_Activator::DB_VERSION ) {

	    Ecomhub_Fi_Activator::activate();
    }
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ecomhub-fi-deactivator.php
 */
function deactivate_ecomhub_fi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ecomhub-fi-deactivator.php';
	Ecomhub_Fi_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ecomhub_fi' );
register_deactivation_hook( __FILE__, 'deactivate_ecomhub_fi' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ecomhub-fi.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ecomhub_fi() {

	$plugin = new Ecomhub_Fi();
	$plugin->run();

}
run_ecomhub_fi();
