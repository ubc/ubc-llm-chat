<?php
/**
 * The shortcode functionality of the plugin.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Public
 */

namespace UBC\LLMChat\Public;

use UBC\LLMChat\Public\UBC_LLM_Chat_Template;

/**
 * The shortcode functionality of the plugin.
 *
 * Handles registration and rendering of the chat shortcode.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Public
 */
class UBC_LLM_Chat_Shortcode {

	/**
	 * Register the shortcode.
	 *
	 * @since    1.0.0
	 */
	public function register() {
		add_shortcode( 'ubc_llm_chat', array( $this, 'render' ) );
	}

	/**
	 * Render the chat shortcode.
	 *
	 * @since    1.0.0
	 * @param    array $atts    Shortcode attributes.
	 * @return   string         Shortcode output.
	 */
	public function render( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'llmservice'        => '',
				'llm'               => '',
				'minimum_user_role' => '',
				'maxmessages'       => 20,
				'maxconversations'  => 10,
				'systemprompt'      => '',
				'temperature'       => 0.7,
				'debug_mode'        => '',
			),
			$atts,
			'ubc_llm_chat'
		);

		// Get global settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Validate and sanitize attributes.
		$instance_settings = $this->validate_attributes( $atts, $settings );

		// Check if there was an error during validation.
		if ( isset( $instance_settings['error'] ) ) {
			return $instance_settings['error'];
		}

		// Generate a unique instance ID for this chat instance.
		$instance_id = 'sc_' . uniqid();

		// Create a template instance.
		$template = new UBC_LLM_Chat_Template( $instance_id, $instance_settings );

		// Render the chat interface.
		return $template->render();
	}

	/**
	 * Validate and sanitize shortcode attributes.
	 *
	 * @since    1.0.0
	 * @param    array $atts      Shortcode attributes.
	 * @param    array $settings  Global plugin settings.
	 * @return   array            Validated and sanitized attributes or error message.
	 */
	private function validate_attributes( $atts, $settings ) {
		$instance_settings = array();

		// Validate LLM service.
		$result = $this->validate_llm_service( $atts, $settings );
		if ( isset( $result['error'] ) ) {
			return $result;
		}
		$instance_settings['llmservice'] = $result['llmservice'];

		// Validate LLM model.
		$result = $this->validate_llm_model( $atts, $instance_settings['llmservice'], $settings );
		if ( isset( $result['error'] ) ) {
			return $result;
		}
		$instance_settings['llm'] = $result['llm'];

		// Validate minimum user role.
		$instance_settings['minimum_user_role'] = $this->validate_minimum_user_role( $atts, $settings );

		// Validate max messages.
		$instance_settings['maxmessages'] = $this->validate_max_messages( $atts );

		// Validate max conversations.
		$instance_settings['maxconversations'] = $this->validate_max_conversations( $atts );

		// Validate system prompt.
		$instance_settings['systemprompt'] = $this->validate_system_prompt( $atts );

		// Validate temperature.
		$instance_settings['temperature'] = $this->validate_temperature( $atts );

		// Validate debug mode.
		$instance_settings['debug_mode'] = $this->validate_debug_mode( $atts );

		return $instance_settings;
	}

	/**
	 * Validate and sanitize LLM service.
	 *
	 * @since    1.0.0
	 * @param    array $atts      Shortcode attributes.
	 * @param    array $settings  Global plugin settings.
	 * @return   array            Validated service or error message.
	 */
	private function validate_llm_service( $atts, $settings ) {
		$llmservice = sanitize_text_field( $atts['llmservice'] );

		if ( empty( $llmservice ) ) {
			// If no service specified, use the first enabled service from settings.
			if ( ! empty( $settings['openai_enabled'] ) && ! empty( $settings['openai_api_key'] ) ) {
				return array( 'llmservice' => 'openai' );
			} elseif ( ! empty( $settings['ollama_enabled'] ) && ! empty( $settings['ollama_url'] ) ) {
				return array( 'llmservice' => 'ollama' );
			} else {
				return array(
					'error' => sprintf(
						'<div class="ubc-llm-chat-error">%s</div>',
						esc_html__( 'No LLM service is currently enabled. Please contact the site administrator.', 'ubc-llm-chat' )
					),
				);
			}
		}

		return array( 'llmservice' => $llmservice );
	}

	/**
	 * Validate and sanitize LLM model.
	 *
	 * @since    1.0.0
	 * @param    array  $atts        Shortcode attributes.
	 * @param    string $llmservice  Validated LLM service.
	 * @param    array  $settings    Global plugin settings.
	 * @return   array               Validated model or error message.
	 */
	private function validate_llm_model( $atts, $llmservice, $settings ) {
		$llm = sanitize_text_field( $atts['llm'] );

		if ( empty( $llm ) ) {
			// If no model specified, use the first available model for the service.
			if ( 'openai' === $llmservice && ! empty( $settings['openai_models'] ) ) {
				return array( 'llm' => reset( $settings['openai_models'] ) );
			} elseif ( 'ollama' === $llmservice && ! empty( $settings['ollama_models'] ) ) {
				return array( 'llm' => reset( $settings['ollama_models'] ) );
			} else {
				return array(
					'error' => sprintf(
						'<div class="ubc-llm-chat-error">%s</div>',
						esc_html__( 'No LLM model is available for the selected service. Please contact the site administrator.', 'ubc-llm-chat' )
					),
				);
			}
		}

		return array( 'llm' => $llm );
	}

	/**
	 * Validate and sanitize minimum user role.
	 *
	 * @since    1.0.0
	 * @param    array $atts      Shortcode attributes.
	 * @param    array $settings  Global plugin settings.
	 * @return   string           Validated minimum user role.
	 */
	private function validate_minimum_user_role( $atts, $settings ) {
		$role = sanitize_text_field( $atts['minimum_user_role'] );

		if ( empty( $role ) ) {
			// If no role specified, use the global setting.
			return isset( $settings['minimum_user_role'] ) ? $settings['minimum_user_role'] : 'subscriber';
		}

		// Validate the role exists.
		$valid_roles = array(
			'subscriber'    => 0,
			'contributor'   => 1,
			'author'        => 2,
			'editor'        => 3,
			'administrator' => 4,
		);

		if ( ! isset( $valid_roles[ $role ] ) ) {
			return isset( $settings['minimum_user_role'] ) ? $settings['minimum_user_role'] : 'subscriber';
		}

		return $role;
	}

	/**
	 * Validate and sanitize max messages.
	 *
	 * @since    1.0.0
	 * @param    array $atts  Shortcode attributes.
	 * @return   int          Validated max messages.
	 */
	private function validate_max_messages( $atts ) {
		$maxmessages = absint( $atts['maxmessages'] );

		if ( $maxmessages < 1 ) {
			return 20;
		}

		return $maxmessages;
	}

	/**
	 * Validate and sanitize max conversations.
	 *
	 * @since    1.0.0
	 * @param    array $atts  Shortcode attributes.
	 * @return   int          Validated max conversations.
	 */
	private function validate_max_conversations( $atts ) {
		$maxconversations = absint( $atts['maxconversations'] );

		if ( $maxconversations < 1 ) {
			return 10;
		}

		return $maxconversations;
	}

	/**
	 * Validate and sanitize system prompt.
	 *
	 * @since    1.0.0
	 * @param    array $atts  Shortcode attributes.
	 * @return   string       Validated system prompt.
	 */
	private function validate_system_prompt( $atts ) {
		return sanitize_textarea_field( $atts['systemprompt'] );
	}

	/**
	 * Validate and sanitize temperature.
	 *
	 * @since    1.0.0
	 * @param    array $atts  Shortcode attributes.
	 * @return   float        Validated temperature.
	 */
	private function validate_temperature( $atts ) {
		$temperature = (float) $atts['temperature'];

		if ( $temperature < 0 || $temperature > 2 ) {
			return 0.7;
		}

		return $temperature;
	}

	/**
	 * Validate and sanitize debug mode.
	 *
	 * @since    1.0.0
	 * @param    array $atts  Shortcode attributes.
	 * @return   bool         Validated debug mode.
	 */
	private function validate_debug_mode( $atts ) {
		if ( '' === $atts['debug_mode'] ) {
			return '';
		}

		return filter_var( $atts['debug_mode'], FILTER_VALIDATE_BOOLEAN );
	}
}
