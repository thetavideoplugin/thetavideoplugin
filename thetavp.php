<?php
/**
 * Plugin Name:       Theta Video Plugin
 * Plugin URI:        https://thetavideoplugin.com
 * Description:       Plugin for using the Theta video API
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Theta Video Plugin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       thetavp
 *
 * @package           thetavp
 */


/**
 * Prevent the plugin from being directly called
 */
if ( ! defined( "ABSPATH" ) ) {
	exit;
}

define( "THETAVP_VERSION", "0.0.1" );

/**
 * Init the Theta Video Plugin Gutenberg block and set the rendering
 */
function thetavp_block_init() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/block.php';
	register_block_type( __DIR__, array(
		'render_callback' => 'render_dynamic_block'
	) );
}

add_action( 'init', 'thetavp_block_init' );

register_activation_hook( __FILE__, 'install' );
/**
 * Install the plugin. Create tables.
 */
function install() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-thetavp-database.php';
	$database = new Thetavp_Database();
	$database->create_database_tables();

	$options = get_option( 'thetavp_api_key' );
	if ( ! $options ) {
		$default = "";
		add_option( 'thetavp_keys', $default );
	}

}

/**
 * Handle the admin menu hook.
 */
function register_menu() {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-thetavp-admin-menu.php';
	$admin_menu = new Thetavp_Admin_Menu();
	$page       = $admin_menu->add_thetavp_menu();

	add_action( 'load-' . $page, 'load_admin' );
}

/**
 * Redirect the post to the page and save the form data.
 */
function save_keys() {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-thetavp-admin-menu.php';
	$admin_menu = new Thetavp_Admin_Menu();
	$admin_menu->thetavp_save_keys();

}

add_action( 'admin_post_thetavp_save_keys', 'save_keys' );


add_action( 'admin_menu', 'register_menu' );

/**
 * Do all the loading required for the admin page
 */
function load_admin() {
	add_action( 'admin_enqueue_scripts', 'enqueue_admin_css' );
}

/**
 * Load the admin page css.
 */
function enqueue_admin_css() {
	wp_enqueue_style( 'admin.css', plugin_dir_url( __FILE__ ) . '/public/css/admin.css' );
}

/**
 * Register rest routes for the API.
 */
function thetavp_register_rest_routes() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-thetavp.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-thetavp-api.php';

	$thetavp          = new Thetavp();
	$api              = new Thetavp_Api();
	$slug             = $thetavp->get_slug();
	$api_version_slug = $slug . '/v1';

	register_rest_route( $api_version_slug, '/get_video/(?P<id>\d+)', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => array( $api, 'get_video' ),
		'permission_callback' => '__return_true'
	) );

	register_rest_route( $api_version_slug, '/get_videos', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => array( $api, 'get_videos' ),
		'permission_callback' => function ( $request ) {
			return current_user_can( 'manage_options' );
		}
	) );
}

/**
 * Set up WP-cron
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-thetavp-cron.php';
cron_setup();

//add_action( 'rest_api_init', 'thetavp_register_rest_routes' );

