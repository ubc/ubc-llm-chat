<?php
/**
 * Test Service Class
 *
 * This class provides test responses for development and testing.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API\Services
 */

namespace UBC\LLMChat\API\Services;

use UBC\LLMChat\API\UBC_LLM_Chat_API_Utils;
use UBC\LLMChat\UBC_LLM_Chat_Filters;

/**
 * Test Service Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_Test_Service extends UBC_LLM_Chat_Service_Base {

	/**
	 * Get a test response.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The model to use (ignored in test mode).
	 * @param    string  $system_prompt   The system prompt (ignored in test mode).
	 * @param    float   $temperature     The temperature setting (ignored in test mode).
	 * @param    integer $timeout         The connection timeout in seconds (ignored in test mode).
	 * @return   string                   The response content.
	 */
	public function get_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout ) {
		return UBC_LLM_Chat_API_Utils::generate_test_response( $content );
	}

	/**
	 * Stream a test response with artificial delays.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The model to use (ignored in test mode).
	 * @param    string  $system_prompt   The system prompt (ignored in test mode).
	 * @param    float   $temperature     The temperature setting (ignored in test mode).
	 * @param    integer $timeout         The connection timeout in seconds (ignored in test mode).
	 * @return   void
	 */
	public function stream_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout ) {
		// Generate a test response.
		$response = UBC_LLM_Chat_API_Utils::generate_test_response( $content );

		// Split the response into words.
		$words = explode( ' ', $response );

		// Initialize the complete response.
		$complete_response = '';

		// Get the conversation ID from the request.
		$conversation_id = isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : '';

		// Stream each word with a small delay.
		foreach ( $words as $word ) {
			$complete_response .= $word . ' ';

			// Apply the message content filter to each word.
			// Note: For streaming, we can't provide the message index since it's not yet saved.
			// We use -1 to indicate it's a streaming chunk.
			$filtered_word = \UBC\LLMChat\UBC_LLM_Chat_Filters::filter_message_content(
				$word . ' ',
				'assistant',
				$conversation_id,
				-1
			);

			// Allow plugins to capture the content for processing.
			apply_filters( 'ubc_llm_chat_capture_content', $word . ' ' );

			// Apply filter to allow plugins to stop sending the chunk to the client.
			// Return false from the filter to stop sending the chunk.
			$should_send = apply_filters( 'ubc_llm_chat_stream_chunk', true );

			if ( false !== $should_send ) {
				UBC_LLM_Chat_API_Utils::send_sse_event( 'message', array( 'content' => $filtered_word ) );
			}

			// Add a small delay to simulate typing.
			usleep( rand( 50000, 150000 ) ); // 50-150ms delay.

			// Flush the output buffer.
			flush();
		}

		// Send a done event to signal the end of the stream.
		UBC_LLM_Chat_API_Utils::send_sse_event( 'done', array() );
	}
}
