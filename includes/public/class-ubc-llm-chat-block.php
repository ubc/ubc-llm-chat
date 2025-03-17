<?php
/**
 * The block functionality of the plugin.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Public
 */

namespace UBC\LLMChat\Public;

use UBC\LLMChat\Public\UBC_LLM_Chat_Template;

/**
 * The block functionality of the plugin.
 *
 * Handles registration and rendering of the chat block.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Public
 */
class UBC_LLM_Chat_Block {

	/**
	 * Register the block.
	 *
	 * @since    1.0.0
	 */
	public function register() {
		// Register block script.
		wp_register_script(
			'ubc-llm-chat-block',
			UBC_LLM_CHAT_URL . 'assets/js/ubc-llm-chat-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ),
			UBC_LLM_CHAT_VERSION,
			true
		);

		// Register block.
		register_block_type(
			'ubc-llm-chat/chat',
			array(
				'editor_script'   => 'ubc-llm-chat-block',
				'render_callback' => array( $this, 'render' ),
				'attributes'      => array(
					'llmservice'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'llm'               => array(
						'type'    => 'string',
						'default' => '',
					),
					'minimum_user_role' => array(
						'type'    => 'string',
						'default' => '',
					),
					'maxmessages'       => array(
						'type'    => 'integer',
						'default' => 20,
					),
					'maxconversations'  => array(
						'type'    => 'integer',
						'default' => 10,
					),
					'systemprompt'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'temperature'       => array(
						'type'    => 'number',
						'default' => 0.7,
					),
					'debug_mode'        => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * Render the chat block.
	 *
	 * @since    1.0.0
	 * @param    array $attributes    The block attributes.
	 * @return   string               The block HTML.
	 */
	public function render( $attributes ) {
		// Get global settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Validate and sanitize attributes.
		$instance_settings = $this->validate_attributes( $attributes, $settings );

		// Check if there was an error during validation.
		if ( isset( $instance_settings['error'] ) ) {
			return $instance_settings['error'];
		}

		// Generate a unique instance ID for this chat instance.
		$instance_id = 'blk_' . uniqid();

		// Create a template instance.
		$template = new UBC_LLM_Chat_Template( $instance_id, $instance_settings );

		// Render the chat interface.
		return $template->render();
	}

	/**
	 * Validate and sanitize block attributes.
	 *
	 * @since    1.0.0
	 * @param    array $attributes  Block attributes.
	 * @param    array $settings    Global plugin settings.
	 * @return   array              Validated and sanitized attributes or error message.
	 */
	private function validate_attributes( $attributes, $settings ) {
		$instance_settings = array();

		// Validate LLM service.
		$result = $this->validate_llm_service( $attributes, $settings );
		if ( isset( $result['error'] ) ) {
			return $result;
		}
		$instance_settings['llmservice'] = $result['llmservice'];

		// Validate LLM model.
		$result = $this->validate_llm_model( $attributes, $instance_settings['llmservice'], $settings );
		if ( isset( $result['error'] ) ) {
			return $result;
		}
		$instance_settings['llm'] = $result['llm'];

		// Validate minimum user role.
		$instance_settings['minimum_user_role'] = $this->validate_minimum_user_role( $attributes, $settings );

		// Validate max messages.
		$instance_settings['maxmessages'] = $this->validate_max_messages( $attributes );

		// Validate max conversations.
		$instance_settings['maxconversations'] = $this->validate_max_conversations( $attributes );

		// Validate system prompt.
		$instance_settings['systemprompt'] = $this->validate_system_prompt( $attributes );

		// Validate temperature.
		$instance_settings['temperature'] = $this->validate_temperature( $attributes );

		// Validate debug mode.
		$instance_settings['debug_mode'] = $this->validate_debug_mode( $attributes );

		return $instance_settings;
	}

	/**
	 * Validate and sanitize LLM service.
	 *
	 * @since    1.0.0
	 * @param    array $attributes  Block attributes.
	 * @param    array $settings    Global plugin settings.
	 * @return   array              Validated service or error message.
	 */
	private function validate_llm_service( $attributes, $settings ) {
		$llmservice = isset( $attributes['llmservice'] ) ? sanitize_text_field( $attributes['llmservice'] ) : '';

		if ( empty( $llmservice ) ) {
			// If no service specified, use the first enabled service.
			if ( isset( $settings['openai_enabled'] ) && $settings['openai_enabled'] ) {
				return array( 'llmservice' => 'openai' );
			} elseif ( isset( $settings['ollama_enabled'] ) && $settings['ollama_enabled'] ) {
				return array( 'llmservice' => 'ollama' );
			} else {
				return array(
					'error' => '<div class="ubc-llm-chat-error">' . esc_html__( 'No LLM service is enabled. Please enable at least one service in the plugin settings.', 'ubc-llm-chat' ) . '</div>',
				);
			}
		}

		return array( 'llmservice' => $llmservice );
	}

	/**
	 * Validate and sanitize LLM model.
	 *
	 * @since    1.0.0
	 * @param    array  $attributes   Block attributes.
	 * @param    string $llmservice   Validated LLM service.
	 * @param    array  $settings     Global plugin settings.
	 * @return   array                Validated model or error message.
	 */
	private function validate_llm_model( $attributes, $llmservice, $settings ) {
		$llm = isset( $attributes['llm'] ) ? sanitize_text_field( $attributes['llm'] ) : '';

		if ( empty( $llm ) ) {
			// If no model specified, use the first available model for the selected service.
			if ( 'openai' === $llmservice && isset( $settings['openai_models'] ) && ! empty( $settings['openai_models'] ) ) {
				return array( 'llm' => $settings['openai_models'][0] );
			} elseif ( 'ollama' === $llmservice && isset( $settings['ollama_models'] ) && ! empty( $settings['ollama_models'] ) ) {
				return array( 'llm' => $settings['ollama_models'][0] );
			} else {
				return array(
					'error' => '<div class="ubc-llm-chat-error">' . esc_html__( 'No LLM model is available for the selected service. Please configure models in the plugin settings.', 'ubc-llm-chat' ) . '</div>',
				);
			}
		}

		return array( 'llm' => $llm );
	}

	/**
	 * Validate and sanitize minimum user role.
	 *
	 * @since    1.0.0
	 * @param    array $attributes  Block attributes.
	 * @param    array $settings    Global plugin settings.
	 * @return   string             Validated minimum user role.
	 */
	private function validate_minimum_user_role( $attributes, $settings ) {
		$role = isset( $attributes['minimum_user_role'] ) ? sanitize_text_field( $attributes['minimum_user_role'] ) : '';

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
	 * @param    array $attributes  Block attributes.
	 * @return   int                Validated max messages.
	 */
	private function validate_max_messages( $attributes ) {
		$maxmessages = isset( $attributes['maxmessages'] ) ? absint( $attributes['maxmessages'] ) : 20;

		if ( $maxmessages < 1 ) {
			return 20;
		}

		return $maxmessages;
	}

	/**
	 * Validate and sanitize max conversations.
	 *
	 * @since    1.0.0
	 * @param    array $attributes  Block attributes.
	 * @return   int                Validated max conversations.
	 */
	private function validate_max_conversations( $attributes ) {
		$maxconversations = isset( $attributes['maxconversations'] ) ? absint( $attributes['maxconversations'] ) : 10;

		if ( $maxconversations < 1 ) {
			return 10;
		}

		return $maxconversations;
	}

	/**
	 * Validate and sanitize system prompt.
	 *
	 * @since    1.0.0
	 * @param    array $attributes  Block attributes.
	 * @return   string             Validated system prompt.
	 */
	private function validate_system_prompt( $attributes ) {
		return isset( $attributes['systemprompt'] ) ? sanitize_textarea_field( $attributes['systemprompt'] ) : '';
	}

	/**
	 * Validate and sanitize temperature.
	 *
	 * @since    1.0.0
	 * @param    array $attributes  Block attributes.
	 * @return   float              Validated temperature.
	 */
	private function validate_temperature( $attributes ) {
		$temperature = isset( $attributes['temperature'] ) ? (float) $attributes['temperature'] : 0.7;

		if ( $temperature < 0 || $temperature > 2 ) {
			return 0.7;
		}

		return $temperature;
	}

	/**
	 * Validate and sanitize debug mode.
	 *
	 * @since    1.0.0
	 * @param    array $attributes  Block attributes.
	 * @return   bool               Validated debug mode.
	 */
	private function validate_debug_mode( $attributes ) {
		return isset( $attributes['debug_mode'] ) ? (bool) $attributes['debug_mode'] : false;
	}
}
