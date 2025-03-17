<?php
/**
 * Message Controller Class
 *
 * This class handles message-related API endpoints.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API
 */

namespace UBC\LLMChat\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use UBC\LLMChat\API\Services\UBC_LLM_Chat_Service_Factory;

/**
 * Message Controller Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_Message_Controller {

	/**
	 * Add a message to a conversation.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 */
	public function add_message( $request ) {
		// Get the conversation ID from the request.
		$conversation_id = sanitize_text_field( $request['id'] );

		// Get the current user ID.
		$user_id = get_current_user_id();

		// Get plugin settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Get the rate limit.
		$rate_limit = isset( $settings['global_rate_limit'] ) ? $settings['global_rate_limit'] : 5;

		// Check if the user is rate limited.
		$remaining_time = 0;
		if ( UBC_LLM_Chat_API_Utils::is_rate_limited_with_time( $user_id, $rate_limit, $remaining_time ) ) {
			// Set the Retry-After header.
			header( 'Retry-After: ' . $remaining_time );

			return new WP_Error(
				'rate_limited',
				/* translators: %d: seconds until next request */
				sprintf(
					esc_html__( 'Rate limited. Please wait %d seconds before sending another message.', 'ubc-llm-chat' ),
					$remaining_time
				),
				array(
					'status'         => 429,
					'remaining_time' => $remaining_time,
				)
			);
		}

		// Get the meta key for the conversation.
		$meta_key = UBC_LLM_Chat_API_Utils::get_conversation_meta_key( $conversation_id );

		// Get the conversation data.
		$conversation = get_user_meta( $user_id, $meta_key, true );

		// Check if the conversation exists.
		if ( ! is_array( $conversation ) ) {
			return new WP_Error(
				'conversation_not_found',
				esc_html__( 'Conversation not found.', 'ubc-llm-chat' ),
				array( 'status' => 404 )
			);
		}

		// Check if the conversation is deleted.
		if ( isset( $conversation['deleted'] ) && $conversation['deleted'] ) {
			return new WP_Error(
				'conversation_deleted',
				esc_html__( 'This conversation has been deleted.', 'ubc-llm-chat' ),
				array( 'status' => 410 )
			);
		}

		// Get the maximum number of messages per conversation.
		$max_messages = isset( $settings['global_max_messages'] ) ? $settings['global_max_messages'] : 20;

		// Check if the conversation has reached its message limit.
		if ( UBC_LLM_Chat_API_Utils::has_reached_message_limit( $conversation, $max_messages ) ) {
			return new WP_Error(
				'message_limit_reached',
				esc_html__( 'This conversation has reached the maximum number of messages allowed.', 'ubc-llm-chat' ),
				array( 'status' => 403 )
			);
		}

		// Get the message content from the request.
		$content = sanitize_textarea_field( $request['content'] );

		// Check if the message is empty.
		if ( empty( $content ) ) {
			return new WP_Error(
				'empty_message',
				esc_html__( 'Message content cannot be empty.', 'ubc-llm-chat' ),
				array( 'status' => 400 )
			);
		}

		// Create the user message.
		$user_message = array(
			'role'      => 'user',
			'content'   => $content,
			'timestamp' => time(),
		);

		// Add the user message to the conversation.
		if ( ! isset( $conversation['messages'] ) ) {
			$conversation['messages'] = array();
		}

		$conversation['messages'][] = $user_message;

		// Update the conversation title if this is the first message.
		if ( count( $conversation['messages'] ) === 1 ) {
			// Use the first 30 characters of the message as the title.
			$title = substr( $content, 0, 30 );
			if ( strlen( $content ) > 30 ) {
				$title .= '...';
			}

			$conversation['title'] = $title;

			// Apply filter to allow plugins to modify the auto-generated conversation name.
			$conversation['title'] = apply_filters( 'ubc_llm_chat_conversation_name', $conversation['title'], $conversation );
		}

		// Update the updated timestamp.
		$conversation['updated'] = time();

		// Check if this is a stream-only request (used when streaming is handled separately).
		$stream_only = isset( $request['stream_only'] ) && $request['stream_only'];

		// If this is a stream-only request, save the conversation and return without generating a response.
		if ( $stream_only ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream-only request, saving user message without generating response' );

			// Save the updated conversation.
			$result = update_user_meta( $user_id, $meta_key, $conversation );

			if ( ! $result ) {
				return new WP_Error(
					'message_addition_failed',
					esc_html__( 'Failed to add the message to the conversation.', 'ubc-llm-chat' ),
					array( 'status' => 500 )
				);
			}

			// Update the last request time for rate limiting.
			UBC_LLM_Chat_API_Utils::update_last_request_time( $user_id );

			// Fire action for message sent.
			do_action( 'ubc_llm_chat_message_sent', $user_message, $conversation, $user_id );

			// Return the updated conversation.
			return rest_ensure_response( $conversation );
		}

		// Save the updated conversation.
		$result = update_user_meta( $user_id, $meta_key, $conversation );

		if ( ! $result ) {
			return new WP_Error(
				'message_addition_failed',
				esc_html__( 'Failed to add the message to the conversation.', 'ubc-llm-chat' ),
				array( 'status' => 500 )
			);
		}

		// Update the last request time for rate limiting.
		UBC_LLM_Chat_API_Utils::update_last_request_time( $user_id );

		// Fire action for message sent.
		do_action( 'ubc_llm_chat_message_sent', $user_message, $conversation, $user_id );

		// Get the LLM service and model from the conversation.
		$llm_service = isset( $conversation['llm_service'] ) ? $conversation['llm_service'] : 'test';
		$llm_model   = isset( $conversation['llm_model'] ) ? $conversation['llm_model'] : 'test';
		$test_mode   = isset( $conversation['test_mode'] ) ? $conversation['test_mode'] : false;

		// Log the LLM service and model for debugging.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Add Message - LLM Service: ' . $llm_service );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Add Message - LLM Model: ' . $llm_model );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Add Message - Test Mode: ' . ( $test_mode ? 'true' : 'false' ) );

		// Get the system prompt and temperature from the conversation.
		$system_prompt = isset( $conversation['system_prompt'] ) ? $conversation['system_prompt'] : '';
		$temperature   = isset( $conversation['temperature'] ) ? $conversation['temperature'] : 0.7;
		$timeout       = 20; // 20 seconds timeout for API requests.

		// Log additional parameters for debugging.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Add Message - System Prompt: ' . $system_prompt );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Add Message - Temperature: ' . $temperature );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Add Message - Timeout: ' . $timeout );

		try {
			// Get the appropriate LLM service.
			$llm_service_instance = UBC_LLM_Chat_Service_Factory::get_service( $llm_service );

			// Get the response from the LLM service.
			$response_content = $llm_service_instance->get_response(
				$conversation,
				$content,
				$llm_model,
				$system_prompt,
				$temperature,
				$timeout
			);
		} catch ( \Exception $e ) {
			// If there's an error, return a placeholder response.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Error getting LLM response: ' . $e->getMessage() );
			$response_content = sprintf(
			/* translators: %s: error message */
				esc_html__( 'Error: %s', 'ubc-llm-chat' ),
				$e->getMessage()
			);
		}

		$ai_message = array(
			'role'      => 'assistant',
			'content'   => $response_content,
			'timestamp' => time(),
		);

		// Add the AI message to the conversation.
		$conversation['messages'][] = $ai_message;

		// Update the updated timestamp.
		$conversation['updated'] = time();

		// Save the updated conversation.
		update_user_meta( $user_id, $meta_key, $conversation );

		// Fire action for message received.
		do_action( 'ubc_llm_chat_message_received', $ai_message, $conversation, $user_id );

		// Return the updated conversation data.
		return new WP_REST_Response( $conversation, 200 );
	}

	/**
	 * Stream a message response.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   void
	 */
	public function stream_message( $request ) {
		// Disable any previous output buffering.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// Set up headers for SSE.
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' ); // Disable buffering for Nginx.

		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message Method Called' );

		// Get the conversation ID from the request.
		$conversation_id = sanitize_text_field( $request['id'] );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - Conversation ID: ' . $conversation_id );

		// Get the current user ID.
		$user_id = get_current_user_id();
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - User ID: ' . $user_id );

		// Get plugin settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Get the rate limit.
		$rate_limit = isset( $settings['global_rate_limit'] ) ? $settings['global_rate_limit'] : 5;

		// Check if the user is rate limited.
		$remaining_time = 0;
		if ( UBC_LLM_Chat_API_Utils::is_rate_limited_with_time( $user_id, $rate_limit, $remaining_time ) ) {
			// Set the Retry-After header.
			header( 'Retry-After: ' . $remaining_time );

			UBC_LLM_Chat_API_Utils::send_sse_error_with_data(
				'rate_limited',
				/* translators: %d: seconds until next request */
				sprintf(
					esc_html__( 'Rate limited. Please wait %d seconds before sending another message.', 'ubc-llm-chat' ),
					$remaining_time
				),
				429,
				array( 'remaining_time' => $remaining_time )
			);
			exit;
		}

		// Get the meta key for the conversation.
		$meta_key = UBC_LLM_Chat_API_Utils::get_conversation_meta_key( $conversation_id );

		// Get the conversation data.
		$conversation = get_user_meta( $user_id, $meta_key, true );

		// Check if the conversation exists.
		if ( ! is_array( $conversation ) ) {
			UBC_LLM_Chat_API_Utils::send_sse_error(
				'conversation_not_found',
				esc_html__( 'Conversation not found.', 'ubc-llm-chat' ),
				404
			);
			exit;
		}

		// Check if the conversation is deleted.
		if ( isset( $conversation['deleted'] ) && $conversation['deleted'] ) {
			UBC_LLM_Chat_API_Utils::send_sse_error(
				'conversation_deleted',
				esc_html__( 'This conversation has been deleted.', 'ubc-llm-chat' ),
				410
			);
			exit;
		}

		// Get the maximum number of messages per conversation.
		$max_messages = isset( $settings['global_max_messages'] ) ? $settings['global_max_messages'] : 20;

		// Check if the conversation has reached its message limit.
		if ( UBC_LLM_Chat_API_Utils::has_reached_message_limit( $conversation, $max_messages ) ) {
			UBC_LLM_Chat_API_Utils::send_sse_error(
				'message_limit_reached',
				esc_html__( 'This conversation has reached the maximum number of messages allowed.', 'ubc-llm-chat' ),
				403
			);
			exit;
		}

		// Get the message content from the request.
		$content = sanitize_textarea_field( $request['content'] );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - Content: ' . $content );

		// Check if the message is empty.
		if ( empty( $content ) ) {
			UBC_LLM_Chat_API_Utils::send_sse_error(
				'empty_message',
				esc_html__( 'Message content cannot be empty.', 'ubc-llm-chat' ),
				400
			);
			exit;
		}

		// Create the user message.
		$user_message = array(
			'role'      => 'user',
			'content'   => $content,
			'timestamp' => time(),
		);
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - User Message: ' . wp_json_encode( $user_message ) );

		// Add the user message to the conversation.
		if ( ! isset( $conversation['messages'] ) ) {
			$conversation['messages'] = array();
		}

		$conversation['messages'][] = $user_message;

		// Update the conversation title if this is the first message.
		if ( count( $conversation['messages'] ) === 1 ) {
			// Use the first 30 characters of the message as the title.
			$title = substr( $content, 0, 30 );
			if ( strlen( $content ) > 30 ) {
				$title .= '...';
			}

			$conversation['title'] = $title;

			// Apply filter to allow plugins to modify the auto-generated conversation name.
			$conversation['title'] = apply_filters( 'ubc_llm_chat_conversation_name', $conversation['title'], $conversation );
		}

		// Update the updated timestamp.
		$conversation['updated'] = time();

		// Save the updated conversation.
		$result = update_user_meta( $user_id, $meta_key, $conversation );

		if ( ! $result ) {
			UBC_LLM_Chat_API_Utils::send_sse_error(
				'message_addition_failed',
				esc_html__( 'Failed to add the message to the conversation.', 'ubc-llm-chat' ),
				500
			);
			exit;
		}

		// Update the last request time for rate limiting.
		UBC_LLM_Chat_API_Utils::update_last_request_time( $user_id );

		// Fire action for message sent.
		do_action( 'ubc_llm_chat_message_sent', $user_message, $conversation, $user_id );

		// Get the LLM service and model from the conversation.
		$llm_service = isset( $conversation['llm_service'] ) ? $conversation['llm_service'] : 'test';
		$llm_model   = isset( $conversation['llm_model'] ) ? $conversation['llm_model'] : 'test';
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - LLM Service: ' . $llm_service );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - LLM Model: ' . $llm_model );

		// Get the system prompt and temperature from the conversation.
		$system_prompt = isset( $conversation['system_prompt'] ) ? $conversation['system_prompt'] : '';
		$temperature   = isset( $conversation['temperature'] ) ? $conversation['temperature'] : 0.7;
		$timeout       = 20; // 20 seconds timeout for API requests.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - System Prompt: ' . $system_prompt );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - Temperature: ' . $temperature );
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - Timeout: ' . $timeout );

		// Variable to capture the full response content.
		$full_response_content = '';

		try {
			// Get the appropriate LLM service.
			$llm_service_instance = UBC_LLM_Chat_Service_Factory::get_service( $llm_service );
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - Got LLM Service Instance: ' . get_class( $llm_service_instance ) );

			// Create a callback function to capture the full response.
			$capture_callback = function ( $chunk ) use ( &$full_response_content ) {
				$full_response_content .= $chunk;
				return true;
			};

			// Stream the response from the LLM service.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - About to call stream_response' );

			// Set a custom callback for capturing the content.
			add_filter( 'ubc_llm_chat_capture_content', $capture_callback, 10, 1 );

			$llm_service_instance->stream_response(
				$conversation,
				$content,
				$llm_model,
				$system_prompt,
				$temperature,
				$timeout
			);

			// Remove the callback.
			remove_filter( 'ubc_llm_chat_capture_content', $capture_callback, 10 );

			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - After calling stream_response' );
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Message - Full Response Content: ' . $full_response_content );

			// Create the AI message with the complete response.
			$ai_message = array(
				'role'      => 'assistant',
				'content'   => $full_response_content,
				'timestamp' => time(),
			);

			// Add the AI message to the conversation.
			$conversation['messages'][] = $ai_message;

			// Update the updated timestamp.
			$conversation['updated'] = time();

			// Save the updated conversation.
			$result = update_user_meta( $user_id, $meta_key, $conversation );

			if ( ! $result ) {
				UBC_LLM_Chat_API_Utils::send_sse_error(
					'message_addition_failed',
					esc_html__( 'Failed to add the message to the conversation.', 'ubc-llm-chat' ),
					500
				);
				exit;
			}

			// Update the last request time for rate limiting.
			UBC_LLM_Chat_API_Utils::update_last_request_time( $user_id );

			// Fire action for message sent.
			do_action( 'ubc_llm_chat_message_sent', $ai_message, $conversation, $user_id );

			// Send a done event with the updated conversation.
			echo "event: done\n";
			echo 'data: ' . json_encode( array( 'conversation' => $conversation ) ) . "\n\n";
			flush();

		} catch ( \Exception $e ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Stream Message - Error: ' . $e->getMessage() );
			UBC_LLM_Chat_API_Utils::send_sse_error(
				'api_error',
				$e->getMessage(),
				500
			);
			exit;
		}
	}

	/**
	 * Get chat settings (available LLMs, rate limits, etc.).
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 */
	public function get_settings( $request ) {
		// Get plugin settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Create a filtered version of the settings to return to the client.
		$client_settings = array(
			'available_llm_services'   => array(),
			'global_rate_limit'        => isset( $settings['global_rate_limit'] ) ? (int) $settings['global_rate_limit'] : 5,
			'global_max_conversations' => isset( $settings['global_max_conversations'] ) ? (int) $settings['global_max_conversations'] : 10,
			'global_max_messages'      => isset( $settings['global_max_messages'] ) ? (int) $settings['global_max_messages'] : 20,
		);

		// Add OpenAI service if enabled.
		if ( isset( $settings['openai_enabled'] ) && $settings['openai_enabled'] ) {
			$client_settings['available_llm_services']['openai'] = array(
				'name'   => esc_html__( 'OpenAI (ChatGPT)', 'ubc-llm-chat' ),
				'models' => isset( $settings['openai_models'] ) ? $settings['openai_models'] : array(),
			);
		}

		// Add Ollama service if enabled.
		if ( isset( $settings['ollama_enabled'] ) && $settings['ollama_enabled'] ) {
			$client_settings['available_llm_services']['ollama'] = array(
				'name'   => esc_html__( 'Ollama', 'ubc-llm-chat' ),
				'models' => isset( $settings['ollama_models'] ) ? $settings['ollama_models'] : array(),
			);
		}

		// Apply filter to allow plugins to modify the available LLM services.
		$client_settings['available_llm_services'] = apply_filters( 'ubc_llm_chat_available_llm_services', $client_settings['available_llm_services'] );

		// Return the settings.
		return new WP_REST_Response( $client_settings, 200 );
	}
}
