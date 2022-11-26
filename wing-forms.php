<?php
/**
 * Plugin Name: Wing Forms
 * Plugin URI: https://github.com/np2861996/wing-forms-Plugin
 * Description: Quick, easy, advance feature rich forms display plugin. 
 * Author: BeyondN
 * Text Domain: wing-forms
 * Version: 1.0.0
 *
 * @package Wing_Forms
 * @author BeyondN
 */

define( 'WFP_VERSION', '1.0.0' );

define( 'WFP_REQUIRED_WP_VERSION', '5.9' );

define( 'WFP_TEXT_DOMAIN', 'wing-forms' );

define( 'WFP_PLUGIN', __FILE__ );

define( 'WFP_PLUGIN_BASENAME', plugin_basename( WFP_PLUGIN ) );

define( 'WFP_PLUGIN_NAME', trim( dirname( WFP_PLUGIN_BASENAME ), '/' ) );

define( 'WFP_PLUGIN_DIR', untrailingslashit( dirname( WFP_PLUGIN ) ) );

define( 'WFP_PLUGIN_MODULES_DIR', WFP_PLUGIN_DIR . '/modules' );

if ( ! defined( 'WFP_LOAD_JS' ) ) {
	define( 'WFP_LOAD_JS', true );
}

if ( ! defined( 'WFP_LOAD_CSS' ) ) {
	define( 'WFP_LOAD_CSS', true );
}

if ( ! defined( 'WFP_AUTOP' ) ) {
	define( 'WFP_AUTOP', true );
}

if ( ! defined( 'WFP_USE_PIPE' ) ) {
	define( 'WFP_USE_PIPE', true );
}

if ( ! defined( 'WFP_ADMIN_READ_CAPABILITY' ) ) {
	define( 'WFP_ADMIN_READ_CAPABILITY', 'edit_posts' );
}

if ( ! defined( 'WFP_ADMIN_READ_WRITE_CAPABILITY' ) ) {
	define( 'WFP_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );
}

if ( ! defined( 'WFP_VERIFY_NONCE' ) ) {
	define( 'WFP_VERIFY_NONCE', false );
}

if ( ! defined( 'WFP_USE_REALLY_SIMPLE_CAPTCHA' ) ) {
	define( 'WFP_USE_REALLY_SIMPLE_CAPTCHA', false );
}

if ( ! defined( 'WFP_VALIDATE_CONFIGURATION' ) ) {
	define( 'WFP_VALIDATE_CONFIGURATION', true );
}

// Deprecated, not used in the plugin core. Use wfp_plugin_url() instead.
define( 'WFP_PLUGIN_URL',
	untrailingslashit( plugins_url( '', WFP_PLUGIN ) )
);

require_once WFP_PLUGIN_DIR . '/load.php';
