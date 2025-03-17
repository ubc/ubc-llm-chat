<?php
/**
 * API Utilities Class
 *
 * This class contains utility functions used by the API controllers.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API
 */

namespace UBC\LLMChat\API;

/**
 * API Utilities Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_API_Utils {

	/**
	 * Generate a UUID for a new conversation.
	 *
	 * @since    1.0.0
	 * @return   string    The generated UUID.
	 */
	public static function generate_uuid() {
		// Generate a version 4 (random) UUID.
		$data = random_bytes( 16 );

		// Set version to 0100.
		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
		// Set bits 6-7 to 10.
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );

		// Output the 36 character UUID.
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}

	/**
	 * Get the meta key for a conversation.
	 *
	 * @since    1.0.0
	 * @param    string $conversation_id    The conversation ID.
	 * @return   string                     The meta key.
	 */
	public static function get_conversation_meta_key( $conversation_id ) {
		return 'ubc_llm_chat_conversation_' . sanitize_key( $conversation_id );
	}

	/**
	 * Check if a user has reached their conversation limit.
	 *
	 * @since    1.0.0
	 * @param    int $user_id               The user ID.
	 * @param    int $max_conversations     The maximum number of conversations allowed.
	 * @return   bool                       True if the user has reached their limit, false otherwise.
	 */
	public static function has_reached_conversation_limit( $user_id, $max_conversations ) {
		global $wpdb;

		// Count all conversations for this user, including deleted ones.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
				$user_id,
				'ubc_llm_chat_conversation_%'
			)
		);

		// Apply filter to allow plugins to modify the limit.
		$max_conversations = apply_filters( 'ubc_llm_chat_max_conversations', $max_conversations, $user_id );

		return (int) $count >= (int) $max_conversations;
	}

	/**
	 * Check if a conversation has reached its message limit.
	 *
	 * @since    1.0.0
	 * @param    array $conversation        The conversation data.
	 * @param    int   $max_messages        The maximum number of messages allowed.
	 * @return   bool                       True if the conversation has reached its limit, false otherwise.
	 */
	public static function has_reached_message_limit( $conversation, $max_messages ) {
		// Count the number of messages in the conversation.
		$count = isset( $conversation['messages'] ) ? count( $conversation['messages'] ) : 0;

		// Apply filter to allow plugins to modify the limit.
		$max_messages = apply_filters( 'ubc_llm_chat_max_messages', $max_messages, $conversation['id'] );

		return (int) $count >= (int) $max_messages;
	}

	/**
	 * Check if a user is rate limited.
	 *
	 * @since    1.0.0
	 * @param    int $user_id               The user ID.
	 * @param    int $rate_limit            The rate limit in seconds.
	 * @return   bool                       True if the user is rate limited, false otherwise.
	 */
	public static function is_rate_limited( $user_id, $rate_limit ) {
		// If rate limit is 0, no rate limiting.
		if ( 0 === (int) $rate_limit ) {
			return false;
		}

		// Get the last request time.
		$last_request_time = (int) get_user_meta( $user_id, 'ubc_llm_chat_last_request_time', true );

		// If no last request time, not rate limited.
		if ( ! $last_request_time ) {
			return false;
		}

		// Apply filter to allow plugins to modify the rate limit.
		$rate_limit = apply_filters( 'ubc_llm_chat_rate_limit', $rate_limit, $user_id );

		// Check if enough time has passed since the last request.
		$time_since_last_request = time() - $last_request_time;

		return $time_since_last_request < $rate_limit;
	}

	/**
	 * Check if a user is rate limited and calculate remaining time.
	 *
	 * @since    1.0.0
	 * @param    int $user_id               The user ID.
	 * @param    int $rate_limit            The rate limit in seconds.
	 * @param    int &$remaining_time       Reference to store the remaining time.
	 * @return   bool                       True if the user is rate limited, false otherwise.
	 */
	public static function is_rate_limited_with_time( $user_id, $rate_limit, &$remaining_time = 0 ) {
		// If rate limit is 0, no rate limiting.
		if ( 0 === (int) $rate_limit ) {
			$remaining_time = 0;
			return false;
		}

		// Get the last request time.
		$last_request_time = (int) get_user_meta( $user_id, 'ubc_llm_chat_last_request_time', true );

		// If no last request time, not rate limited.
		if ( ! $last_request_time ) {
			$remaining_time = 0;
			return false;
		}

		// Apply filter to allow plugins to modify the rate limit.
		$rate_limit = apply_filters( 'ubc_llm_chat_rate_limit', $rate_limit, $user_id );

		// Check if enough time has passed since the last request.
		$time_since_last_request = time() - $last_request_time;
		$remaining_time          = $rate_limit - $time_since_last_request;

		if ( $remaining_time < 0 ) {
			$remaining_time = 0;
		}

		return $time_since_last_request < $rate_limit;
	}

	/**
	 * Update the last request time for a user.
	 *
	 * @since    1.0.0
	 * @param    int $user_id               The user ID.
	 * @return   void
	 */
	public static function update_last_request_time( $user_id ) {
		update_user_meta( $user_id, 'ubc_llm_chat_last_request_time', time() );
	}

	/**
	 * Format a conversation for export as markdown.
	 *
	 * @since    1.0.0
	 * @param    array $conversation        The conversation data.
	 * @return   string                     The formatted markdown.
	 */
	public static function format_conversation_for_export( $conversation ) {
		// Start with the conversation title.
		$markdown = '# ' . esc_html( $conversation['title'] ) . "\n\n";

		// Add conversation metadata.
		$markdown .= '**Conversation ID:** ' . esc_html( $conversation['id'] ) . "\n";
		$markdown .= '**Created:** ' . esc_html( gmdate( 'Y-m-d H:i:s', $conversation['created'] ) ) . " GMT\n";

		if ( isset( $conversation['llm_service'] ) ) {
			$markdown .= '**LLM Service:** ' . esc_html( $conversation['llm_service'] ) . "\n";
		}

		if ( isset( $conversation['llm_model'] ) ) {
			$markdown .= '**LLM Model:** ' . esc_html( $conversation['llm_model'] ) . "\n";
		}

		$markdown .= "\n---\n\n";

		// Add each message.
		if ( isset( $conversation['messages'] ) && is_array( $conversation['messages'] ) ) {
			foreach ( $conversation['messages'] as $message ) {
				// Add the role and timestamp.
				$markdown .= '**' . esc_html( ucfirst( $message['role'] ) ) . '** ';
				$markdown .= '(' . esc_html( gmdate( 'Y-m-d H:i:s', $message['timestamp'] ) ) . " GMT)\n\n";

				// Add the message content.
				$markdown .= $message['content'] . "\n\n";

				// Add a separator.
				$markdown .= "---\n\n";
			}
		}

		// Apply filter to allow plugins to modify the export format.
		$markdown = apply_filters( 'ubc_llm_chat_export_format', $markdown, $conversation );

		return $markdown;
	}

	/**
	 * Send an SSE event.
	 *
	 * @since    1.0.0
	 * @param    string $event    The event name.
	 * @param    array  $data     The event data.
	 * @return   void
	 */
	public static function send_sse_event( $event, $data ) {
		// Log the SSE event for debugging.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Sending SSE Event: ' . $event . ' - ' . wp_json_encode( $data ) );

		echo 'event: ' . esc_html( $event ) . "\n";
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";

		// Ensure output is sent immediately.
		flush();
	}

	/**
	 * Send an SSE error event.
	 *
	 * @since    1.0.0
	 * @param    string $code       The error code.
	 * @param    string $message    The error message.
	 * @param    int    $status     The HTTP status code.
	 * @return   void
	 */
	public static function send_sse_error( $code, $message, $status ) {
		// Log the SSE error for debugging.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Sending SSE Error: ' . $code . ' - ' . $message . ' (Status: ' . $status . ')' );

		header( 'HTTP/1.1 ' . $status );
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );

		$error_data = array(
			'code'    => $code,
			'message' => $message,
			'status'  => $status,
		);

		echo "event: error_event\n";
		echo 'data: ' . wp_json_encode( $error_data ) . "\n\n";
		flush();
	}

	/**
	 * Send an SSE error event with additional data.
	 *
	 * @since    1.0.0
	 * @param    string $code       The error code.
	 * @param    string $message    The error message.
	 * @param    int    $status     The HTTP status code.
	 * @param    array  $data       Additional data to include in the event.
	 * @return   void
	 */
	public static function send_sse_error_with_data( $code, $message, $status, $data = array() ) {
		// Log the SSE error for debugging.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Sending SSE Error with data: ' . $code . ' - ' . $message . ' (Status: ' . $status . ')' );

		header( 'HTTP/1.1 ' . $status );
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );

		$error_data = array(
			'code'    => $code,
			'message' => $message,
			'status'  => $status,
		);

		// Merge additional data.
		if ( ! empty( $data ) ) {
			$error_data = array_merge( $error_data, $data );
		}

		echo "event: error_event\n";
		echo 'data: ' . wp_json_encode( $error_data ) . "\n\n";
		flush();
	}

	/**
	 * Generate a test response for a given content.
	 *
	 * @since    1.0.0
	 * @param    string $content    The input content.
	 * @return   string             The generated test response.
	 */
	public static function generate_test_response( $content ) {
		// Simple responses based on keywords in the content.
		$content = strtolower( $content );

		if ( strpos( $content, 'hello' ) !== false || strpos( $content, 'hi' ) !== false ) {
			return esc_html__( 'Hello! How can I help you today?', 'ubc-llm-chat' );
		}

		if ( strpos( $content, 'how are you' ) !== false ) {
			return esc_html__( 'I\'m just a test response, but I\'m functioning well! How can I assist you?', 'ubc-llm-chat' );
		}

		if ( strpos( $content, 'help' ) !== false ) {
			return esc_html__( 'I\'m here to help! You can ask me questions, and I\'ll do my best to provide useful information. Note that this is a test mode, so my responses are pre-programmed.', 'ubc-llm-chat' );
		}

		if ( strpos( $content, 'thank' ) !== false ) {
			return esc_html__( 'You\'re welcome! Is there anything else I can help you with?', 'ubc-llm-chat' );
		}

		if ( strpos( $content, '?' ) !== false ) {
			return esc_html__( 'That\'s an interesting question. In test mode, I can only provide pre-programmed responses. When the actual LLM integration is implemented, I\'ll be able to give you more specific answers.', 'ubc-llm-chat' );
		}

		// Default response.
		return esc_html__( 'I understand you\'re testing the chat interface. This is a simulated response in test mode. The actual LLM integration will be implemented in a future update, which will provide more intelligent and contextual responses.', 'ubc-llm-chat' );
	}
}
