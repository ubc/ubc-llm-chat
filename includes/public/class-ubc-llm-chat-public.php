<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Public
 */

namespace UBC\LLMChat\Public;

use UBC\LLMChat\Public\UBC_LLM_Chat_Template;
use UBC\LLMChat\Public\UBC_LLM_Chat_Shortcode;
use UBC\LLMChat\Public\UBC_LLM_Chat_Block;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the public-facing side of the site.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Public
 */
class UBC_LLM_Chat_Public {

	/**
	 * The shortcode handler instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      UBC_LLM_Chat_Shortcode    $shortcode    The shortcode handler instance.
	 */
	private $shortcode;

	/**
	 * The block handler instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      UBC_LLM_Chat_Block    $block    The block handler instance.
	 */
	private $block;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->shortcode = new UBC_LLM_Chat_Shortcode();
		$this->block     = new UBC_LLM_Chat_Block();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Only enqueue styles if the shortcode or block is present on the page.
		if ( $this->is_chat_on_page() ) {
			wp_enqueue_style(
				'ubc-llm-chat-public',
				UBC_LLM_CHAT_URL . 'assets/css/ubc-llm-chat-public.css',
				array(),
				UBC_LLM_CHAT_VERSION,
				'all'
			);

			// Enqueue KaTeX CSS.
			wp_enqueue_style(
				'katex',
				UBC_LLM_CHAT_URL . 'assets/vendor/katex.min.css',
				array(),
				'0.16.21',
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Only enqueue scripts if the shortcode or block is present on the page.
		if ( $this->is_chat_on_page() ) {
			// Enqueue Marked.js library for Markdown formatting.
			wp_enqueue_script(
				'marked',
				UBC_LLM_CHAT_URL . 'assets/vendor/marked.min.js',
				array(),
				'15.0.7',
				true
			);

			// Enqueue KaTeX library for LaTeX formatting.
			wp_enqueue_script(
				'katex',
				UBC_LLM_CHAT_URL . 'assets/vendor/katex.min.js',
				array(),
				'0.16.21',
				true
			);

			// Enqueue utility functions first.
			wp_enqueue_script(
				'ubc-llm-chat-utils',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-utils.js',
				array(),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue formatter base.
			wp_enqueue_script(
				'ubc-llm-chat-formatter-base',
				UBC_LLM_CHAT_URL . 'assets/js/formatters/ubc-llm-chat-formatter-base.js',
				array(),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue formatter utils.
			wp_enqueue_script(
				'ubc-llm-chat-formatter-utils',
				UBC_LLM_CHAT_URL . 'assets/js/formatters/ubc-llm-chat-formatter-utils.js',
				array( 'ubc-llm-chat-formatter-base' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue formatter manager.
			wp_enqueue_script(
				'ubc-llm-chat-formatter-manager',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-formatter-manager.js',
				array( 'ubc-llm-chat-formatter-base' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue formatter registry.
			wp_enqueue_script(
				'ubc-llm-chat-formatter-registry',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-formatter-registry.js',
				array( 'ubc-llm-chat-formatter-manager' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue markdown formatter.
			wp_enqueue_script(
				'ubc-llm-chat-markdown-formatter',
				UBC_LLM_CHAT_URL . 'assets/js/formatters/ubc-llm-chat-markdown-formatter.js',
				array( 'ubc-llm-chat-formatter-base', 'marked', 'ubc-llm-chat-formatter-utils' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue LaTeX formatter.
			wp_enqueue_script(
				'ubc-llm-chat-latex-formatter',
				UBC_LLM_CHAT_URL . 'assets/js/formatters/ubc-llm-chat-latex-formatter.js',
				array( 'ubc-llm-chat-formatter-base', 'katex', 'ubc-llm-chat-formatter-utils' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue animation controller.
			wp_enqueue_script(
				'ubc-llm-chat-animation',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-animation.js',
				array( 'ubc-llm-chat-utils', 'ubc-llm-chat-formatter-manager' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue UI functions.
			wp_enqueue_script(
				'ubc-llm-chat-ui',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-ui.js',
				array( 'ubc-llm-chat-utils' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue API functions.
			wp_enqueue_script(
				'ubc-llm-chat-api',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-api.js',
				array( 'ubc-llm-chat-utils' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue conversation management functions.
			wp_enqueue_script(
				'ubc-llm-chat-conversations',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-conversations.js',
				array( 'ubc-llm-chat-api', 'ubc-llm-chat-ui' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue message handling functions.
			wp_enqueue_script(
				'ubc-llm-chat-messages',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-messages.js',
				array( 'ubc-llm-chat-api', 'ubc-llm-chat-ui' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue event handling functions.
			wp_enqueue_script(
				'ubc-llm-chat-events',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-events.js',
				array( 'ubc-llm-chat-conversations', 'ubc-llm-chat-messages' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Enqueue core functionality.
			wp_enqueue_script(
				'ubc-llm-chat-core',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-core.js',
				array( 'ubc-llm-chat-events' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// For backward compatibility, register the main public script as a dependency of the core.
			wp_enqueue_script(
				'ubc-llm-chat-public',
				UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-public.js',
				array( 'ubc-llm-chat-core' ),
				UBC_LLM_CHAT_VERSION,
				true
			);

			// Localize the script with data needed for API calls.
			wp_localize_script(
				'ubc-llm-chat-utils', // Localize to the first script that will be loaded.
				'ubc_llm_chat_public',
				array(
					'rest_url'          => esc_url_raw( rest_url( 'ubc-llm-chat/v1' ) ),
					'nonce'             => wp_create_nonce( 'wp_rest' ),
					'is_user_logged_in' => is_user_logged_in(),
					'streaming_enabled' => true,
					'message_templates' => UBC_LLM_Chat_Template::get_message_templates(),
					'i18n'              => array(
						'error'              => esc_html__( 'Error', 'ubc-llm-chat' ),
						'success'            => esc_html__( 'Success', 'ubc-llm-chat' ),
						'warning'            => esc_html__( 'Warning', 'ubc-llm-chat' ),
						'info'               => esc_html__( 'Info', 'ubc-llm-chat' ),
						'loading'            => esc_html__( 'Loading...', 'ubc-llm-chat' ),
						'sending'            => esc_html__( 'Sending...', 'ubc-llm-chat' ),
						'thinking'           => esc_html__( 'Thinking...', 'ubc-llm-chat' ),
						'deleted'            => esc_html__( 'Conversation deleted successfully.', 'ubc-llm-chat' ),
						'renamed'            => esc_html__( 'Conversation renamed successfully.', 'ubc-llm-chat' ),
						'rate_limited'       => esc_html__( 'Rate limited. Please wait for the countdown to complete before sending another message.', 'ubc-llm-chat' ),
						'network_error'      => esc_html__( 'Network error. Please check your connection and try again.', 'ubc-llm-chat' ),
						'server_error'       => esc_html__( 'Server error. Please try again later.', 'ubc-llm-chat' ),
						'unknown_error'      => esc_html__( 'An unknown error occurred. Please try again.', 'ubc-llm-chat' ),
						'message_limit'      => esc_html__( 'This conversation has reached the maximum number of messages allowed.', 'ubc-llm-chat' ),
						'conversation_limit' => esc_html__( 'You have reached the maximum number of conversations allowed.', 'ubc-llm-chat' ),
						'empty_message'      => esc_html__( 'Please enter a message.', 'ubc-llm-chat' ),
						'empty_name'         => esc_html__( 'Please enter a name for the conversation.', 'ubc-llm-chat' ),
						'just_now'           => esc_html__( 'just now', 'ubc-llm-chat' ),
						'minute_ago'         => esc_html__( '1 minute ago', 'ubc-llm-chat' ),
						/* translators: %d: number of minutes */
						'minutes_ago'        => esc_html__( '%d minutes ago', 'ubc-llm-chat' ),
						'hour_ago'           => esc_html__( '1 hour ago', 'ubc-llm-chat' ),
						/* translators: %d: number of hours */
						'hours_ago'          => esc_html__( '%d hours ago', 'ubc-llm-chat' ),
						'yesterday'          => esc_html__( 'yesterday', 'ubc-llm-chat' ),
						/* translators: %d: number of days */
						'days_ago'           => esc_html__( '%d days ago', 'ubc-llm-chat' ),
					),
				)
			);
		}
	}

	/**
	 * Register shortcodes.
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes() {
		$this->shortcode->register();
	}

	/**
	 * Register blocks.
	 *
	 * @since    1.0.0
	 */
	public function register_blocks() {
		$this->block->register();
	}

	/**
	 * Check if the chat is on the current page.
	 *
	 * @since    1.0.0
	 * @return   boolean    True if the chat is on the page, false otherwise.
	 */
	private function is_chat_on_page() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		// Check for shortcode.
		if ( has_shortcode( $post->post_content, 'ubc_llm_chat' ) ) {
			return true;
		}

		// Check for block.
		if ( function_exists( 'has_block' ) && has_block( 'ubc-llm-chat/chat', $post->post_content ) ) {
			return true;
		}

		return false;
	}
}
