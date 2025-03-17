<?php
/**
 * UBC LLM Chat
 *
 * @package           UBC\LLMChat
 * @author            UBC
 * @copyright         2023 UBC
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       UBC LLM Chat
 * Plugin URI:        https://github.com/ubc/ubc-llm-chat
 * Description:       A WordPress plugin that creates a chat interface between the website and various large language models via their APIs.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Author:            UBC
 * Author URI:        https://ubc.ca
 * Text Domain:       ubc-llm-chat
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/ubc/ubc-llm-chat
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'UBC_LLM_CHAT_VERSION', '1.0.0' );

/**
 * Plugin base path.
 */
define( 'UBC_LLM_CHAT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin base URL.
 */
define( 'UBC_LLM_CHAT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload dependencies.
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * The code that runs during plugin activation.
 */
function ubc_llm_chat_activate() {
	// Activation code here.
	// We don't need custom database tables, so this is minimal.

	// Set default options if they don't exist.
	if ( ! get_option( 'ubc_llm_chat_settings' ) ) {
		$default_settings = array(
			'openai_enabled'           => false,
			'openai_api_key'           => '',
			'openai_models'            => array(),
			'ollama_enabled'           => false,
			'ollama_url'               => 'http://localhost:11434/api/',
			'ollama_models'            => array(),
			'global_rate_limit'        => 5,
			'global_max_conversations' => 10,
			'global_max_messages'      => 20,
			'connection_timeout'       => 30,
			'minimum_user_role'        => 'subscriber',
			'debug_mode'               => false,
		);

		add_option( 'ubc_llm_chat_settings', $default_settings );
	}
}

/**
 * The code that runs during plugin deactivation.
 */
function ubc_llm_chat_deactivate() {
	// Deactivation code here.
	// We don't need to do anything special on deactivation.
}

register_activation_hook( __FILE__, 'ubc_llm_chat_activate' );
register_deactivation_hook( __FILE__, 'ubc_llm_chat_deactivate' );

/**
 * Initialize the plugin.
 */
function ubc_llm_chat_init() {
	// Load plugin text domain for translations.
	load_plugin_textdomain( 'ubc-llm-chat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Include the core plugin class.
	require_once UBC_LLM_CHAT_PATH . 'includes/class-ubc-llm-chat.php';

	// Initialize the plugin.
	$plugin = new UBC\LLMChat\UBC_LLM_Chat();
	$plugin->run();
}

// Initialize the plugin after all plugins are loaded.
add_action( 'plugins_loaded', 'ubc_llm_chat_init' );
