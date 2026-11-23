<?php
/**
 * Plugin Name: Chatbot
 * Plugin URI:  https://github.com/terrykernan/chatbot
 * Description: AI-powered chat widget for woocommerce stores. Provider-agnostic (Claude/OpenAI), fully managed from WordPress admin.
 * Version:     1.0.0
 * Author:      Terry Kernan <terry@terrykernan.co.uk>
 * Text Domain: woo-chatbot
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'WOO_CHATBOT_VERSION', '1.0.0' );
define( 'WOO_CHATBOT_FILE', __FILE__ );
define( 'WOO_CHATBOT_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOO_CHATBOT_URL', plugin_dir_url( __FILE__ ) );

require_once WOO_CHATBOT_DIR . 'includes/class-activator.php';
require_once WOO_CHATBOT_DIR . 'includes/class-deactivator.php';

register_activation_hook( __FILE__, [ 'WOO_CHATBOT_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WOO_CHATBOT_Deactivator', 'deactivate' ] );

function WOO_CHATBOT_run(): void {
	require_once WOO_CHATBOT_DIR . 'includes/class-woo-chatbot.php';
	( new WOO_CHATBOT() )->run();
}
add_action( 'plugins_loaded', 'WOO_CHATBOT_run' );
