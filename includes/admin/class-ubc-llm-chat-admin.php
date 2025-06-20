<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Admin
 */

namespace UBC\LLMChat\Admin;

use UBC\LLMChat\UBC_LLM_Chat_Debug;
use UBC\LLMChat\API\UBC_LLM_Chat_API_Key_Manager;
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Admin
 */
class UBC_LLM_Chat_Admin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_ubc_llm_chat_test_openai_connection', array( $this, 'test_openai_connection' ) );
		add_action( 'wp_ajax_ubc_llm_chat_test_ollama_connection', array( $this, 'test_ollama_connection' ) );
		add_action( 'wp_ajax_ubc_llm_chat_fetch_openai_models', array( $this, 'fetch_openai_models' ) );
		add_action( 'wp_ajax_ubc_llm_chat_fetch_ollama_models', array( $this, 'fetch_ollama_models' ) );

		// Add hook for storing all available models.
		add_action( 'ubc_llm_chat_models_fetched', array( $this, 'store_available_models' ), 10, 2 );

		// Add help tab.
		add_action( 'admin_head', array( $this, 'add_help_tab' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();

		// Only load styles on our settings page.
		if ( 'settings_page_ubc-llm-chat' === $screen->id ) {
			wp_enqueue_style(
				'ubc-llm-chat-admin',
				UBC_LLM_CHAT_URL . 'assets/css/ubc-llm-chat-admin.css',
				array(),
				UBC_LLM_CHAT_VERSION,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		// Only load scripts on our settings page.
		if ( 'settings_page_ubc-llm-chat' === $screen->id ) {
			wp_enqueue_script(
				'ubc-llm-chat-admin',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-admin.js',
				array(),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Localize the script with data needed for AJAX calls.
			wp_localize_script(
				'ubc-llm-chat-admin',
				'ubc_llm_chat_admin',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'ubc_llm_chat_admin_nonce' ),
				)
			);
		}
	}

	/**
	 * Add the settings page to the admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'UBC LLM Chat Settings', 'ubc-llm-chat' ),
			__( 'UBC LLM Chat', 'ubc-llm-chat' ),
			'manage_options',
			'ubc-llm-chat',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display the settings page content.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add admin header.
		require_once UBC_LLM_CHAT_PATH . 'includes/admin/partials/ubc-llm-chat-admin-header.php';

		// Get current tab.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'llm_services';

		// Include the appropriate tab content.
		switch ( $active_tab ) {
			case 'global_settings':
				require_once UBC_LLM_CHAT_PATH . 'includes/admin/partials/ubc-llm-chat-admin-global-settings.php';
				break;
			case 'usage_tracking':
				require_once UBC_LLM_CHAT_PATH . 'includes/admin/partials/ubc-llm-chat-admin-usage-tracking.php';
				break;
			case 'llm_services':
			default:
				require_once UBC_LLM_CHAT_PATH . 'includes/admin/partials/ubc-llm-chat-admin-llm-services.php';
				break;
		}

		// Add admin footer.
		require_once UBC_LLM_CHAT_PATH . 'includes/admin/partials/ubc-llm-chat-admin-footer.php';
	}

	/**
	 * Add help tab to the settings page.
	 *
	 * @since    1.0.0
	 */
	public function add_help_tab() {
		$screen = get_current_screen();

		// Only add help tab on our settings page.
		if ( ! $screen || 'settings_page_ubc-llm-chat' !== $screen->id ) {
			return;
		}

		// Add help tab.
		$screen->add_help_tab(
			array(
				'id'      => 'ubc_llm_chat_help_tab',
				'title'   => __( 'Help', 'ubc-llm-chat' ),
				'content' => '<h2>' . __( 'UBC LLM Chat Help', 'ubc-llm-chat' ) . '</h2>' .
					'<p>' . __( 'This plugin allows you to add a chat interface to your WordPress site that connects to various large language models (LLMs) via their APIs.', 'ubc-llm-chat' ) . '</p>' .
					'<h3>' . __( 'LLM Services', 'ubc-llm-chat' ) . '</h3>' .
					'<p>' . __( 'You can enable one or more LLM services:', 'ubc-llm-chat' ) . '</p>' .
					'<ul>' .
					'<li><strong>' . __( 'OpenAI (ChatGPT)', 'ubc-llm-chat' ) . '</strong> - ' . __( 'Requires an API key from OpenAI. Visit <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API Keys</a> to get one.', 'ubc-llm-chat' ) . '</li>' .
					'<li><strong>' . __( 'Ollama', 'ubc-llm-chat' ) . '</strong> - ' . __( 'Requires a running Ollama server. Visit <a href="https://ollama.ai" target="_blank">Ollama</a> for more information.', 'ubc-llm-chat' ) . '</li>' .
					'</ul>' .
					'<h3>' . __( 'Global Settings', 'ubc-llm-chat' ) . '</h3>' .
					'<p>' . __( 'Configure global settings that apply to all instances of the chat interface:', 'ubc-llm-chat' ) . '</p>' .
					'<ul>' .
					'<li><strong>' . __( 'Global Rate Limit', 'ubc-llm-chat' ) . '</strong> - ' . __( 'The number of seconds between API requests.', 'ubc-llm-chat' ) . '</li>' .
					'<li><strong>' . __( 'Global Maximum Conversations', 'ubc-llm-chat' ) . '</strong> - ' . __( 'The maximum number of conversations a user can have.', 'ubc-llm-chat' ) . '</li>' .
					'<li><strong>' . __( 'Global Maximum Messages', 'ubc-llm-chat' ) . '</strong> - ' . __( 'The maximum number of messages per conversation.', 'ubc-llm-chat' ) . '</li>' .
					'<li><strong>' . __( 'Connection Timeout', 'ubc-llm-chat' ) . '</strong> - ' . __( 'The number of seconds to wait for a response from the API.', 'ubc-llm-chat' ) . '</li>' .
					'<li><strong>' . __( 'Minimum User Role', 'ubc-llm-chat' ) . '</strong> - ' . __( 'The minimum user role required to access the chat interface.', 'ubc-llm-chat' ) . '</li>' .
					'</ul>' .
					'<h3>' . __( 'Usage Tracking', 'ubc-llm-chat' ) . '</h3>' .
					'<p>' . __( 'View usage statistics for the chat interface, including the number of conversations and messages.', 'ubc-llm-chat' ) . '</p>' .
					'<h3>' . __( 'Shortcode', 'ubc-llm-chat' ) . '</h3>' .
					'<p>' . __( 'Use the <code>[ubc_llm_chat]</code> shortcode to add the chat interface to a post or page. You can customize the shortcode with various attributes:', 'ubc-llm-chat' ) . '</p>' .
					'<ul>' .
					'<li><code>llmservice</code> - ' . __( 'The LLM service to use (e.g., "chatgpt" or "ollama").', 'ubc-llm-chat' ) . '</li>' .
					'<li><code>llm</code> - ' . __( 'The specific model to use (e.g., "gpt-4" or "llama3").', 'ubc-llm-chat' ) . '</li>' .
					'<li><code>minimum_user_role</code> - ' . __( 'The minimum user role required to access the chat (default: subscriber).', 'ubc-llm-chat' ) . '</li>' .
					'<li><code>maxmessages</code> - ' . __( 'The maximum number of messages per conversation (default: 20).', 'ubc-llm-chat' ) . '</li>' .
					'<li><code>maxconversations</code> - ' . __( 'The maximum number of conversations a user can have (default: 10).', 'ubc-llm-chat' ) . '</li>' .
					'<li><code>systemprompt</code> - ' . __( 'The system prompt for the conversation.', 'ubc-llm-chat' ) . '</li>' .
					'<li><code>temperature</code> - ' . __( 'The temperature setting for the model (default: 0.7).', 'ubc-llm-chat' ) . '</li>' .
					'<li><code>debug_mode</code> - ' . __( 'Enable debug mode for this instance (default: false).', 'ubc-llm-chat' ) . '</li>' .
					'</ul>' .
					'<h3>' . __( 'Block', 'ubc-llm-chat' ) . '</h3>' .
					'<p>' . __( 'You can also add the chat interface using the UBC LLM Chat block in the block editor.', 'ubc-llm-chat' ) . '</p>',
			)
		);

		// Add sidebar.
		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'ubc-llm-chat' ) . '</strong></p>' .
			'<p><a href="https://github.com/ubc/ubc-llm-chat" target="_blank">' . __( 'GitHub Repository', 'ubc-llm-chat' ) . '</a></p>' .
			'<p><a href="https://github.com/ubc/ubc-llm-chat/issues" target="_blank">' . __( 'Report Issues', 'ubc-llm-chat' ) . '</a></p>'
		);
	}

	/**
	 * Register all settings for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// Register the settings with proper arguments.
		register_setting(
			'ubc_llm_chat_settings',
			'ubc_llm_chat_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// LLM Services Section.
		add_settings_section(
			'ubc_llm_chat_llm_services_section',
			__( 'LLM Services', 'ubc-llm-chat' ),
			array( $this, 'llm_services_section_callback' ),
			'ubc-llm-chat-llm-services'
		);

		// OpenAI Settings.
		add_settings_field(
			'openai_enabled',
			__( 'Enable OpenAI (ChatGPT)', 'ubc-llm-chat' ),
			array( $this, 'openai_enabled_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		add_settings_field(
			'openai_api_key',
			__( 'OpenAI API Key', 'ubc-llm-chat' ),
			array( $this, 'openai_api_key_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		add_settings_field(
			'openai_fetch_models',
			__( 'Available OpenAI Models', 'ubc-llm-chat' ),
			array( $this, 'openai_fetch_models_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		add_settings_field(
			'openai_temperature',
			__( 'OpenAI Temperature', 'ubc-llm-chat' ),
			array( $this, 'openai_temperature_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		// Ollama Settings.
		add_settings_field(
			'ollama_enabled',
			__( 'Enable Ollama', 'ubc-llm-chat' ),
			array( $this, 'ollama_enabled_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		add_settings_field(
			'ollama_url',
			__( 'Ollama URL', 'ubc-llm-chat' ),
			array( $this, 'ollama_url_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		add_settings_field(
			'ollama_api_key',
			__( 'Ollama API Key', 'ubc-llm-chat' ),
			array( $this, 'ollama_api_key_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		add_settings_field(
			'ollama_fetch_models',
			__( 'Available Ollama Models', 'ubc-llm-chat' ),
			array( $this, 'ollama_fetch_models_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		add_settings_field(
			'ollama_temperature',
			__( 'Ollama Temperature', 'ubc-llm-chat' ),
			array( $this, 'ollama_temperature_callback' ),
			'ubc-llm-chat-llm-services',
			'ubc_llm_chat_llm_services_section'
		);

		// Global Settings Section.
		add_settings_section(
			'ubc_llm_chat_global_settings_section',
			__( 'Global Settings', 'ubc-llm-chat' ),
			array( $this, 'global_settings_section_callback' ),
			'ubc-llm-chat-global-settings'
		);

		add_settings_field(
			'global_rate_limit',
			__( 'Global Rate Limit (seconds)', 'ubc-llm-chat' ),
			array( $this, 'global_rate_limit_callback' ),
			'ubc-llm-chat-global-settings',
			'ubc_llm_chat_global_settings_section'
		);

		add_settings_field(
			'global_max_conversations',
			__( 'Global Maximum Conversations per User', 'ubc-llm-chat' ),
			array( $this, 'global_max_conversations_callback' ),
			'ubc-llm-chat-global-settings',
			'ubc_llm_chat_global_settings_section'
		);

		add_settings_field(
			'global_max_messages',
			__( 'Global Maximum Messages per Conversation', 'ubc-llm-chat' ),
			array( $this, 'global_max_messages_callback' ),
			'ubc-llm-chat-global-settings',
			'ubc_llm_chat_global_settings_section'
		);

		add_settings_field(
			'connection_timeout',
			__( 'Connection Timeout (seconds)', 'ubc-llm-chat' ),
			array( $this, 'connection_timeout_callback' ),
			'ubc-llm-chat-global-settings',
			'ubc_llm_chat_global_settings_section'
		);

		add_settings_field(
			'minimum_user_role',
			__( 'Minimum User Role', 'ubc-llm-chat' ),
			array( $this, 'minimum_user_role_callback' ),
			'ubc-llm-chat-global-settings',
			'ubc_llm_chat_global_settings_section'
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'ubc-llm-chat' ),
			array( $this, 'debug_mode_callback' ),
			'ubc-llm-chat-global-settings',
			'ubc_llm_chat_global_settings_section'
		);
	}

	/**
	 * Callback for the LLM Services section.
	 *
	 * @since    1.0.0
	 */
	public function llm_services_section_callback() {
		echo '<p>' . esc_html__( 'Configure the LLM services you want to make available on your site.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the Global Settings section.
	 *
	 * @since    1.0.0
	 */
	public function global_settings_section_callback() {
		echo '<p>' . esc_html__( 'Configure global settings for the UBC LLM Chat plugin.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Sanitize the settings.
	 *
	 * @since    1.0.0
	 * @param    array $input The settings to sanitize.
	 * @return   array           The sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		// Get existing settings to preserve values not in the current tab.
		$existing_settings = get_option( 'ubc_llm_chat_settings', array() );
		$sanitized_input   = $existing_settings; // Start with existing settings.

		// Determine which tab is being saved based on the presence of tab-specific fields.
		$current_tab = '';
		if ( isset( $input['openai_enabled'] ) || isset( $input['openai_api_key'] ) || isset( $input['openai_models'] ) ||
			isset( $input['ollama_enabled'] ) || isset( $input['ollama_url'] ) || isset( $input['ollama_api_key'] ) || isset( $input['ollama_models'] ) ) {
			$current_tab = 'llm_services';
		} elseif ( isset( $input['global_rate_limit'] ) || isset( $input['global_max_conversations'] ) ||
					isset( $input['global_max_messages'] ) || isset( $input['connection_timeout'] ) ||
					isset( $input['minimum_user_role'] ) || isset( $input['debug_mode'] ) ) {
			$current_tab = 'global_settings';
		}

		// Define which checkbox fields belong to which tab.
		$tab_checkbox_fields = array(
			'llm_services'    => array( 'openai_enabled', 'ollama_enabled' ),
			'global_settings' => array( 'debug_mode' ),
		);

		// Only process checkbox fields for the current tab.
		if ( ! empty( $current_tab ) && isset( $tab_checkbox_fields[ $current_tab ] ) ) {
			foreach ( $tab_checkbox_fields[ $current_tab ] as $field ) {
				$sanitized_input[ $field ] = isset( $input[ $field ] ) ? (bool) $input[ $field ] : false;
			}
		}

		// Process the rest of the fields as before.
		// OpenAI settings.
		if ( isset( $input['openai_api_key'] ) ) {
			if ( ! empty( $input['openai_api_key'] ) ) {
				$sanitized_input['openai_api_key'] = $this->encrypt_api_key( sanitize_text_field( $input['openai_api_key'] ) );
			} else {
				// If empty string is submitted, clear the saved API key.
				$sanitized_input['openai_api_key'] = '';
			}
		}

		if ( isset( $input['openai_models'] ) ) {
			$sanitized_input['openai_models'] = $input['openai_models'];
		} elseif ( isset( $input['openai_enabled'] ) && $input['openai_enabled'] ) {
			// Only reset models if we're enabling OpenAI and no models were provided.
			$sanitized_input['openai_models'] = array();
		}

		// Preserve available models.
		if ( isset( $input['openai_available_models'] ) ) {
			$sanitized_input['openai_available_models'] = $input['openai_available_models'];
		}

		if ( isset( $input['openai_temperature'] ) ) {
			$sanitized_input['openai_temperature'] = floatval( $input['openai_temperature'] );
			// Ensure temperature is between 0 and 1.
			$sanitized_input['openai_temperature'] = max( 0, min( 1, $sanitized_input['openai_temperature'] ) );
		}

		// Ollama settings.
		if ( isset( $input['ollama_url'] ) ) {
			$sanitized_input['ollama_url'] = esc_url_raw( $input['ollama_url'] );
		}

		if ( isset( $input['ollama_api_key'] ) ) {
			if ( ! empty( $input['ollama_api_key'] ) ) {
				$sanitized_input['ollama_api_key'] = $this->encrypt_api_key( sanitize_text_field( $input['ollama_api_key'] ) );
			} else {
				// If empty string is submitted, clear the saved API key.
				$sanitized_input['ollama_api_key'] = '';
			}
		}

		if ( isset( $input['ollama_models'] ) ) {
			$sanitized_input['ollama_models'] = $input['ollama_models'];
		} elseif ( isset( $input['ollama_enabled'] ) && $input['ollama_enabled'] ) {
			// Only reset models if we're enabling Ollama and no models were provided.
			$sanitized_input['ollama_models'] = array();
		}

		// Preserve available models.
		if ( isset( $input['ollama_available_models'] ) ) {
			$sanitized_input['ollama_available_models'] = $input['ollama_available_models'];
		}

		if ( isset( $input['ollama_temperature'] ) ) {
			$sanitized_input['ollama_temperature'] = floatval( $input['ollama_temperature'] );
			// Ensure temperature is between 0 and 1.
			$sanitized_input['ollama_temperature'] = max( 0, min( 1, $sanitized_input['ollama_temperature'] ) );
		}

		// Global settings.
		if ( isset( $input['global_rate_limit'] ) ) {
			$sanitized_input['global_rate_limit'] = absint( $input['global_rate_limit'] );
		}

		if ( isset( $input['global_max_conversations'] ) ) {
			$sanitized_input['global_max_conversations'] = absint( $input['global_max_conversations'] );
		}

		if ( isset( $input['global_max_messages'] ) ) {
			$sanitized_input['global_max_messages'] = absint( $input['global_max_messages'] );
		}

		if ( isset( $input['connection_timeout'] ) ) {
			$sanitized_input['connection_timeout'] = absint( $input['connection_timeout'] );
		}

		if ( isset( $input['minimum_user_role'] ) ) {
			$sanitized_input['minimum_user_role'] = sanitize_text_field( $input['minimum_user_role'] );
		}

		// Fire action after settings are saved.
		do_action( 'ubc_llm_chat_settings_saved', $sanitized_input );

		return $sanitized_input;
	}

	/**
	 * Encrypt an API key for secure storage.
	 *
	 * @since    1.0.0
	 * @param    string $api_key    The API key to encrypt.
	 * @return   string             The encrypted API key.
	 */
	private function encrypt_api_key( $api_key ) {
		return UBC_LLM_Chat_API_Key_Manager::encrypt_api_key( $api_key );
	}

	/**
	 * Decrypt an API key for use in API requests.
	 *
	 * @since    1.0.0
	 * @param    string $encrypted_api_key    The encrypted API key.
	 * @return   string                       The decrypted API key.
	 */
	public function decrypt_api_key( $encrypted_api_key ) {
		return UBC_LLM_Chat_API_Key_Manager::decrypt_api_key( $encrypted_api_key );
	}

	/**
	 * Callback for the OpenAI enabled field.
	 *
	 * @since    1.0.0
	 */
	public function openai_enabled_callback() {
		$settings = get_option( 'ubc_llm_chat_settings' );
		$checked  = isset( $settings['openai_enabled'] ) ? $settings['openai_enabled'] : false;

		echo '<label for="openai_enabled">';
		echo '<input type="checkbox" id="openai_enabled" name="ubc_llm_chat_settings[openai_enabled]" value="1" ' . checked( $checked, true, false ) . ' />';
		echo esc_html__( 'Enable OpenAI (ChatGPT) integration', 'ubc-llm-chat' );
		echo '</label>';
		echo '<p class="description">' . esc_html__( 'Check this box to enable OpenAI (ChatGPT) integration.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the OpenAI API key field.
	 *
	 * @since    1.0.0
	 */
	public function openai_api_key_callback() {
		$settings = get_option( 'ubc_llm_chat_settings' );
		$api_key  = isset( $settings['openai_api_key'] ) ? $this->decrypt_api_key( $settings['openai_api_key'] ) : '';
		$disabled = isset( $settings['openai_enabled'] ) && $settings['openai_enabled'] ? '' : 'disabled';

		echo '<input type="password" id="openai_api_key" name="ubc_llm_chat_settings[openai_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text" ' . esc_attr( $disabled ) . ' />';
		echo '<p class="description">' . esc_html__( 'Enter your OpenAI API key. This is required for OpenAI integration to work.', 'ubc-llm-chat' ) . '</p>';

		// Add a test connection button.
		if ( isset( $settings['openai_enabled'] ) && $settings['openai_enabled'] ) {
			echo '<button type="button" id="test_openai_connection" class="button button-secondary">' . esc_html__( 'Test Connection', 'ubc-llm-chat' ) . '</button>';
			echo '<span id="openai_connection_result" class="connection-result"></span>';
		}
	}

	/**
	 * Callback for the OpenAI fetch models field.
	 *
	 * @since    1.0.0
	 */
	public function openai_fetch_models_callback() {
		$settings         = get_option( 'ubc_llm_chat_settings' );
		$disabled         = isset( $settings['openai_enabled'] ) && $settings['openai_enabled'] ? '' : 'disabled';
		$models           = isset( $settings['openai_models'] ) ? $settings['openai_models'] : array();
		$available_models = isset( $settings['openai_available_models'] ) ? $settings['openai_available_models'] : array();

		echo '<button type="button" id="fetch_openai_models" class="button button-secondary" ' . esc_attr( $disabled ) . '>' . esc_html__( 'Fetch Available Models', 'ubc-llm-chat' ) . '</button>';
		echo '<span id="openai_models_result" class="models-result"></span>';

		echo '<div id="openai_models_container" class="models-container">';
		if ( ! empty( $available_models ) ) {
			echo '<p>' . esc_html__( 'Available Models:', 'ubc-llm-chat' ) . '</p>';
			echo '<ul>';
			foreach ( $available_models as $model_id => $model_name ) {
				$checked = isset( $models[ $model_id ] ) ? 'checked' : '';
				echo '<li>';
				echo '<label>';
				echo '<input type="checkbox" name="ubc_llm_chat_settings[openai_models][' . esc_attr( $model_id ) . ']" value="' . esc_attr( $model_name ) . '" ' . esc_attr( $checked ) . ' />';
				echo esc_html( $model_name );
				echo '</label>';
				echo '</li>';
				// Add a hidden field to preserve available models data on form submission.
				echo '<input type="hidden" name="ubc_llm_chat_settings[openai_available_models][' . esc_attr( $model_id ) . ']" value="' . esc_attr( $model_name ) . '" />';
			}
			echo '</ul>';
		}
		echo '</div>';

		echo '<p class="description">' . esc_html__( 'Click the button to fetch available models from OpenAI. You must have a valid API key.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the OpenAI temperature field.
	 *
	 * @since    1.0.0
	 */
	public function openai_temperature_callback() {
		$settings    = get_option( 'ubc_llm_chat_settings' );
		$temperature = isset( $settings['openai_temperature'] ) ? $settings['openai_temperature'] : 0.7;

		echo '<input type="number" id="openai_temperature" name="ubc_llm_chat_settings[openai_temperature]" value="' . esc_attr( $temperature ) . '" class="small-text" min="0" step="0.1" />';
		echo '<p class="description">' . esc_html__( 'Enter the temperature for OpenAI models.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the Ollama enabled field.
	 *
	 * @since    1.0.0
	 */
	public function ollama_enabled_callback() {
		$settings = get_option( 'ubc_llm_chat_settings' );
		$checked  = isset( $settings['ollama_enabled'] ) ? $settings['ollama_enabled'] : false;

		echo '<label for="ollama_enabled">';
		echo '<input type="checkbox" id="ollama_enabled" name="ubc_llm_chat_settings[ollama_enabled]" value="1" ' . checked( $checked, true, false ) . ' />';
		echo esc_html__( 'Enable Ollama integration', 'ubc-llm-chat' );
		echo '</label>';
		echo '<p class="description">' . esc_html__( 'Check this box to enable Ollama integration.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the Ollama URL field.
	 *
	 * @since    1.0.0
	 */
	public function ollama_url_callback() {
		$settings = get_option( 'ubc_llm_chat_settings' );
		$url      = isset( $settings['ollama_url'] ) ? $settings['ollama_url'] : 'http://localhost:11434/api/';
		$disabled = isset( $settings['ollama_enabled'] ) && $settings['ollama_enabled'] ? '' : 'disabled';

		echo '<input type="text" id="ollama_url" name="ubc_llm_chat_settings[ollama_url]" value="' . esc_attr( $url ) . '" class="regular-text" ' . esc_attr( $disabled ) . ' />';
		echo '<p class="description">' . esc_html__( 'Enter the URL for your Ollama server. Default is http://localhost:11434/api/', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the Ollama API key field.
	 *
	 * @since    1.0.0
	 */
	public function ollama_api_key_callback() {
		$settings = get_option( 'ubc_llm_chat_settings' );
		$api_key  = isset( $settings['ollama_api_key'] ) ? $this->decrypt_api_key( $settings['ollama_api_key'] ) : '';
		$disabled = isset( $settings['ollama_enabled'] ) && $settings['ollama_enabled'] ? '' : 'disabled';

		echo '<input type="password" id="ollama_api_key" name="ubc_llm_chat_settings[ollama_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text" ' . esc_attr( $disabled ) . ' />';
		echo '<p class="description">' . esc_html__( 'Enter your Ollama API key if required by your Ollama server configuration.', 'ubc-llm-chat' ) . '</p>';

		// Add a test connection button.
		if ( isset( $settings['ollama_enabled'] ) && $settings['ollama_enabled'] ) {
			echo '<button type="button" id="test_ollama_connection" class="button button-secondary">' . esc_html__( 'Test Connection', 'ubc-llm-chat' ) . '</button>';
			echo '<span id="ollama_connection_result" class="connection-result"></span>';
		}
	}

	/**
	 * Callback for the Ollama fetch models field.
	 *
	 * @since    1.0.0
	 */
	public function ollama_fetch_models_callback() {
		$settings         = get_option( 'ubc_llm_chat_settings' );
		$disabled         = isset( $settings['ollama_enabled'] ) && $settings['ollama_enabled'] ? '' : 'disabled';
		$models           = isset( $settings['ollama_models'] ) ? $settings['ollama_models'] : array();
		$available_models = isset( $settings['ollama_available_models'] ) ? $settings['ollama_available_models'] : array();

		echo '<button type="button" id="fetch_ollama_models" class="button button-secondary" ' . esc_attr( $disabled ) . '>' . esc_html__( 'Fetch Available Models', 'ubc-llm-chat' ) . '</button>';
		echo '<span id="ollama_models_result" class="models-result"></span>';

		echo '<div id="ollama_models_container" class="models-container">';
		if ( ! empty( $available_models ) ) {
			echo '<p>' . esc_html__( 'Available Models:', 'ubc-llm-chat' ) . '</p>';
			echo '<ul>';
			foreach ( $available_models as $model_id => $model_name ) {
				$checked = isset( $models[ $model_id ] ) ? 'checked' : '';
				echo '<li>';
				echo '<label>';
				echo '<input type="checkbox" name="ubc_llm_chat_settings[ollama_models][' . esc_attr( $model_id ) . ']" value="' . esc_attr( $model_name ) . '" ' . esc_attr( $checked ) . ' />';
				echo esc_html( $model_name );
				echo '</label>';
				echo '</li>';
				// Add a hidden field to preserve available models data on form submission.
				echo '<input type="hidden" name="ubc_llm_chat_settings[ollama_available_models][' . esc_attr( $model_id ) . ']" value="' . esc_attr( $model_name ) . '" />';
			}
			echo '</ul>';
		}
		echo '</div>';

		echo '<p class="description">' . esc_html__( 'Click the button to fetch available models from your Ollama server.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the Ollama temperature field.
	 *
	 * @since    1.0.0
	 */
	public function ollama_temperature_callback() {
		$settings    = get_option( 'ubc_llm_chat_settings' );
		$temperature = isset( $settings['ollama_temperature'] ) ? $settings['ollama_temperature'] : 0.7;

		echo '<input type="number" id="ollama_temperature" name="ubc_llm_chat_settings[ollama_temperature]" value="' . esc_attr( $temperature ) . '" class="small-text" min="0" step="0.1" />';
		echo '<p class="description">' . esc_html__( 'Enter the temperature for Ollama models.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the global rate limit field.
	 *
	 * @since    1.0.0
	 */
	public function global_rate_limit_callback() {
		$settings   = get_option( 'ubc_llm_chat_settings' );
		$rate_limit = isset( $settings['global_rate_limit'] ) ? $settings['global_rate_limit'] : 5;

		echo '<input type="number" id="global_rate_limit" name="ubc_llm_chat_settings[global_rate_limit]" value="' . esc_attr( $rate_limit ) . '" class="small-text" min="0" step="1" />';
		echo '<p class="description">' . esc_html__( 'Enter the number of seconds between API requests. Set to 0 to disable rate limiting.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the global maximum conversations field.
	 *
	 * @since    1.0.0
	 */
	public function global_max_conversations_callback() {
		$settings          = get_option( 'ubc_llm_chat_settings' );
		$max_conversations = isset( $settings['global_max_conversations'] ) ? $settings['global_max_conversations'] : 10;

		echo '<input type="number" id="global_max_conversations" name="ubc_llm_chat_settings[global_max_conversations]" value="' . esc_attr( $max_conversations ) . '" class="small-text" min="1" step="1" />';
		echo '<p class="description">' . esc_html__( 'Enter the maximum number of conversations a user can have.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the global maximum messages field.
	 *
	 * @since    1.0.0
	 */
	public function global_max_messages_callback() {
		$settings     = get_option( 'ubc_llm_chat_settings' );
		$max_messages = isset( $settings['global_max_messages'] ) ? $settings['global_max_messages'] : 20;

		echo '<input type="number" id="global_max_messages" name="ubc_llm_chat_settings[global_max_messages]" value="' . esc_attr( $max_messages ) . '" class="small-text" min="1" step="1" />';
		echo '<p class="description">' . esc_html__( 'Enter the maximum number of messages per conversation.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the connection timeout field.
	 *
	 * @since    1.0.0
	 */
	public function connection_timeout_callback() {
		$settings = get_option( 'ubc_llm_chat_settings' );
		$timeout  = isset( $settings['connection_timeout'] ) ? $settings['connection_timeout'] : 30;

		echo '<input type="number" id="connection_timeout" name="ubc_llm_chat_settings[connection_timeout]" value="' . esc_attr( $timeout ) . '" class="small-text" min="1" step="1" />';
		echo '<p class="description">' . esc_html__( 'Enter the connection timeout in seconds.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the minimum user role field.
	 *
	 * @since    1.0.0
	 */
	public function minimum_user_role_callback() {
		$settings = get_option( 'ubc_llm_chat_settings' );
		$role     = isset( $settings['minimum_user_role'] ) ? $settings['minimum_user_role'] : 'subscriber';

		$roles = array(
			'subscriber'    => __( 'Subscriber', 'ubc-llm-chat' ),
			'contributor'   => __( 'Contributor', 'ubc-llm-chat' ),
			'author'        => __( 'Author', 'ubc-llm-chat' ),
			'editor'        => __( 'Editor', 'ubc-llm-chat' ),
			'administrator' => __( 'Administrator', 'ubc-llm-chat' ),
		);

		echo '<select id="minimum_user_role" name="ubc_llm_chat_settings[minimum_user_role]">';
		foreach ( $roles as $role_value => $role_name ) {
			echo '<option value="' . esc_attr( $role_value ) . '" ' . selected( $role, $role_value, false ) . '>' . esc_html( $role_name ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select the minimum user role required to access the chat interface.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Callback for the debug mode field.
	 *
	 * @since    1.0.0
	 */
	public function debug_mode_callback() {
		$settings = get_option( 'ubc_llm_chat_settings' );
		$checked  = isset( $settings['debug_mode'] ) ? $settings['debug_mode'] : false;

		echo '<label for="debug_mode">';
		echo '<input type="checkbox" id="debug_mode" name="ubc_llm_chat_settings[debug_mode]" value="1" ' . checked( $checked, true, false ) . ' />';
		echo esc_html__( 'Enable debug mode', 'ubc-llm-chat' );
		echo '</label>';
		echo '<p class="description">' . esc_html__( 'Check this box to enable debug mode. This will output debug information to the wp-content/debug.log file.', 'ubc-llm-chat' ) . '</p>';
	}

	/**
	 * Test OpenAI connection.
	 *
	 * @since    1.0.0
	 */
	public function test_openai_connection() {
		// Check nonce.
		if ( ! check_ajax_referer( 'ubc_llm_chat_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'ubc-llm-chat' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'ubc-llm-chat' ) );
		}

		// Get API key from settings.
		$settings = get_option( 'ubc_llm_chat_settings' );
		$api_key  = isset( $settings['openai_api_key'] ) ? $this->decrypt_api_key( $settings['openai_api_key'] ) : '';

		if ( empty( $api_key ) ) {
			wp_send_json_error( __( 'API key is required.', 'ubc-llm-chat' ) );
		}

		try {
			// Test connection by fetching models directly.
			$models = $this->get_openai_models( $api_key );

			if ( empty( $models ) ) {
				wp_send_json_error( __( 'No models found.', 'ubc-llm-chat' ) );
			}

			// Fire action after connection test.
			do_action( 'ubc_llm_chat_api_connection_tested', 'openai', true );

			// Skip firing the models_fetched action when just testing connection.
			// This prevents the settings from being updated unnecessarily.

			wp_send_json_success();
		} catch ( \Exception $e ) {
			// Log error if debug mode is enabled.
			UBC_LLM_Chat_Debug::exception( $e, 'OpenAI connection test failed' );

			// Fire action after connection test.
			do_action( 'ubc_llm_chat_api_connection_tested', 'openai', false, $e->getMessage() );

			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Test Ollama connection.
	 *
	 * @since    1.0.0
	 */
	public function test_ollama_connection() {
		// Check nonce.
		if ( ! check_ajax_referer( 'ubc_llm_chat_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'ubc-llm-chat' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'ubc-llm-chat' ) );
		}

		// Get URL.
		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( __( 'URL is required.', 'ubc-llm-chat' ) );
		}

		// Get API key from settings.
		$settings = get_option( 'ubc_llm_chat_settings' );
		$api_key  = isset( $settings['ollama_api_key'] ) ? $this->decrypt_api_key( $settings['ollama_api_key'] ) : '';

		if ( empty( $api_key ) ) {
			wp_send_json_error( __( 'API key is required.', 'ubc-llm-chat' ) );
		}

		try {
			// Ensure URL ends with a trailing slash.
			$url = trailingslashit( $url );

			// Make a direct HTTP request using WordPress's HTTP API.
			$api_url = $url . 'tags';

			$request_args = array(
				'timeout' => 30,
			);

			// Add API key to headers if provided.
			if ( ! empty( $api_key ) ) {
				$request_args['headers'] = array(
					'Authorization' => 'Bearer ' . $api_key,
				);
			}

			$response = wp_remote_get(
				$api_url,
				$request_args
			);

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( $response->get_error_message() );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				$error_message = wp_remote_retrieve_response_message( $response );
				wp_send_json_error( 'API Error: ' . $response_code . ' ' . $error_message );
			}

			$body        = wp_remote_retrieve_body( $response );
			$models_data = json_decode( $body, true );

			$models = isset( $models_data['models'] ) ? $models_data['models'] : array();

			if ( empty( $models ) ) {
				wp_send_json_error( __( 'No models found.', 'ubc-llm-chat' ) );
			}

			// Fire action after connection test.
			do_action( 'ubc_llm_chat_api_connection_tested', 'ollama', true );

			// Skip firing the models_fetched action when just testing connection.
			// This prevents the settings from being updated unnecessarily.

			wp_send_json_success();
		} catch ( \Exception $e ) {
			// Log error if debug mode is enabled.
			UBC_LLM_Chat_Debug::exception( $e, 'Ollama connection test failed' );

			// Fire action after connection test.
			do_action( 'ubc_llm_chat_api_connection_tested', 'ollama', false, $e->getMessage() );

			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Fetch OpenAI models.
	 *
	 * @since    1.0.0
	 */
	public function fetch_openai_models() {
		// Check nonce.
		if ( ! check_ajax_referer( 'ubc_llm_chat_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'ubc-llm-chat' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'ubc-llm-chat' ) );
		}

		// Get API key from settings.
		$settings = get_option( 'ubc_llm_chat_settings' );
		$api_key  = isset( $settings['openai_api_key'] ) ? $this->decrypt_api_key( $settings['openai_api_key'] ) : '';

		if ( empty( $api_key ) ) {
			wp_send_json_error( __( 'API key is required.', 'ubc-llm-chat' ) );
		}

		try {
			// Fetch models directly.
			$models = $this->get_openai_models( $api_key );

			if ( empty( $models ) ) {
				wp_send_json_error( __( 'No models found.', 'ubc-llm-chat' ) );
			}

			// Fire action after models are fetched.
			do_action( 'ubc_llm_chat_models_fetched', 'openai', $models );

			wp_send_json_success( $models );
		} catch ( \Exception $e ) {
			// Log error if debug mode is enabled.
			UBC_LLM_Chat_Debug::exception( $e, 'OpenAI models fetch failed' );

			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Fetch Ollama models.
	 *
	 * @since    1.0.0
	 */
	public function fetch_ollama_models() {
		// Check nonce.
		if ( ! check_ajax_referer( 'ubc_llm_chat_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'ubc-llm-chat' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'ubc-llm-chat' ) );
		}

		UBC_LLM_Chat_Debug::info( 'Fetching Ollama models' );

		try {
			// Get URL.
			$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

			if ( empty( $url ) ) {
				wp_send_json_error( __( 'URL is required.', 'ubc-llm-chat' ) );
			}

			// Get API key from settings.
			$settings = get_option( 'ubc_llm_chat_settings' );
			$api_key  = isset( $settings['ollama_api_key'] ) ? $this->decrypt_api_key( $settings['ollama_api_key'] ) : '';

			if ( empty( $api_key ) ) {
				wp_send_json_error( __( 'API key is required.', 'ubc-llm-chat' ) );
			}

			// Ensure URL ends with a trailing slash.
			$url = trailingslashit( $url );

			// Make a direct HTTP request using WordPress's HTTP API.
			$api_url = $url . 'tags';

			$request_args = array(
				'timeout' => 30,
			);

			// Add API key to headers if provided.
			if ( ! empty( $api_key ) ) {
				$request_args['headers'] = array(
					'Authorization' => 'Bearer ' . $api_key,
				);
			}

			$response = wp_remote_get(
				$api_url,
				$request_args
			);

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( $response->get_error_message() );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				$error_message = wp_remote_retrieve_response_message( $response );
				wp_send_json_error( 'API Error: ' . $response_code . ' ' . $error_message );
			}

			$body        = wp_remote_retrieve_body( $response );
			$models_data = json_decode( $body, true );

			$models = isset( $models_data['models'] ) ? $models_data['models'] : array();

			if ( empty( $models ) ) {
				wp_send_json_error( __( 'No models found.', 'ubc-llm-chat' ) );
			}

			// Fire action after models are fetched.
			do_action( 'ubc_llm_chat_models_fetched', 'ollama', $models );

			wp_send_json_success( $models );
		} catch ( \Exception $e ) {
			// Log error if debug mode is enabled.
			UBC_LLM_Chat_Debug::exception( $e, 'Ollama models fetch failed' );

			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Get OpenAI models from the API.
	 *
	 * @since    1.0.0
	 * @param    string $api_key The OpenAI API key.
	 * @return   array               The list of models.
	 * @throws   \Exception          If there is an error with the API request.
	 */
	private function get_openai_models( $api_key ) {
		// Make a direct HTTP request using WordPress's HTTP API.
		$api_url = 'https://api.openai.com/v1/models';

		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( esc_html( $response->get_error_message() ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );

		// Check for API errors in the response.
		if ( isset( $response_data['error'] ) ) {
			$error_message = isset( $response_data['error']['message'] ) ? $response_data['error']['message'] : 'Unknown API error';
			throw new \Exception( esc_html( $error_message ) );
		}

		// Check for non-200 status code.
		if ( 200 !== $response_code ) {
			$error_message = wp_remote_retrieve_response_message( $response );
			throw new \Exception( 'API Error: ' . esc_html( $response_code ) . ' ' . esc_html( $error_message ) );
		}

		return isset( $response_data['data'] ) ? $response_data['data'] : array();
	}

	/**
	 * Store all available models when they are fetched.
	 *
	 * @since    1.0.0
	 * @param    string $service    The service name.
	 * @param    array  $models     The fetched models.
	 */
	public function store_available_models( $service, $models ) {
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Store all available models.
		if ( 'openai' === $service ) {
			// Format OpenAI models.
			$all_models = array();
			foreach ( $models as $model ) {
				$all_models[ $model['id'] ] = $model['id'];
			}
			$settings['openai_available_models'] = $all_models;
		} elseif ( 'ollama' === $service ) {
			// Format Ollama models.
			$all_models = array();
			foreach ( $models as $model ) {
				$all_models[ $model['name'] ] = $model['name'];
			}
			$settings['ollama_available_models'] = $all_models;
		}

		// Update the settings without touching API keys.
		// This should be called only from fetch_models methods, not from test_connection methods.
		update_option( 'ubc_llm_chat_settings', $settings );
	}
}
