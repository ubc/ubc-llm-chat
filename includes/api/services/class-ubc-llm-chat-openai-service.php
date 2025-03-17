<?php
/**
 * OpenAI Service Class
 *
 * This class handles interactions with the OpenAI API.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API\Services
 */

namespace UBC\LLMChat\API\Services;

use UBC\LLMChat\API\UBC_LLM_Chat_API_Utils;
use UBC\LLMChat\UBC_LLM_Chat_Filters;

/**
 * OpenAI Service Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_OpenAI_Service extends UBC_LLM_Chat_Service_Base {

	/**
	 * Get a response from OpenAI.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The OpenAI model to use.
	 * @param    string  $system_prompt   The system prompt.
	 * @param    float   $temperature     The temperature setting.
	 * @param    integer $timeout         The connection timeout in seconds.
	 * @return   string                   The response content.
	 * @throws   \Exception               If there is an error with the API request.
	 */
	public function get_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout ) {
		// Get plugin settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Get the API key.
		$api_key = isset( $settings['openai_api_key'] ) ? $settings['openai_api_key'] : '';

		// Check if the API key is set.
		if ( empty( $api_key ) ) {
			throw new \Exception( esc_html__( 'OpenAI API key is not set.', 'ubc-llm-chat' ) );
		}

		// Prepare the messages array.
		$messages = $this->prepare_messages( $conversation, $content, $system_prompt );

		// Prepare the request data.
		$request_data = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => (float) $temperature,
		);

		// Initialize cURL.
		$ch = curl_init();

		// Set cURL options.
		curl_setopt( $ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $request_data ) );
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $api_key,
			)
		);
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );

		// Execute the cURL request.
		$response = curl_exec( $ch );

		// Check for cURL errors.
		$curl_error = curl_error( $ch );
		if ( ! empty( $curl_error ) ) {
			curl_close( $ch );
			throw new \Exception(
				sprintf(
				/* translators: %s: cURL error message */
					esc_html__( 'cURL error: %s', 'ubc-llm-chat' ),
					esc_html( $curl_error )
				)
			);
		}

		// Get the HTTP status code.
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		// Close the cURL handle.
		curl_close( $ch );

		// Check for HTTP errors.
		if ( 200 !== $http_code ) {
			$error_data    = json_decode( $response, true );
			$error_message = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : esc_html__( 'Unknown error', 'ubc-llm-chat' );
			throw new \Exception(
				sprintf(
				/* translators: 1: HTTP status code, 2: error message */
					esc_html__( 'HTTP error %1$d: %2$s', 'ubc-llm-chat' ),
					esc_html( $http_code ),
					esc_html( $error_message )
				)
			);
		}

		// Decode the response.
		$response_data = json_decode( $response, true );

		// Check if the response is valid.
		if ( ! isset( $response_data['choices'][0]['message']['content'] ) ) {
			throw new \Exception( esc_html__( 'Invalid response from OpenAI API', 'ubc-llm-chat' ) );
		}

		// Return the response content.
		return $response_data['choices'][0]['message']['content'];
	}

	/**
	 * Stream a response from OpenAI.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The OpenAI model to use.
	 * @param    string  $system_prompt   The system prompt.
	 * @param    float   $temperature     The temperature setting.
	 * @param    integer $timeout         The connection timeout in seconds.
	 * @return   void
	 * @throws   \Exception               If there is an error with the API request.
	 */
	public function stream_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout ) {
		// Get plugin settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Get the API key.
		$api_key = isset( $settings['openai_api_key'] ) ? $settings['openai_api_key'] : '';

		// Check if the API key is set.
		if ( empty( $api_key ) ) {
			throw new \Exception( esc_html__( 'OpenAI API key is not set.', 'ubc-llm-chat' ) );
		}

		// Prepare the messages array.
		$messages = $this->prepare_messages( $conversation, $content, $system_prompt );

		// Prepare the request data.
		$request_data = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => (float) $temperature,
			'stream'      => true, // Enable streaming.
		);

		// Initialize cURL.
		$ch = curl_init();

		// Set cURL options.
		curl_setopt( $ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $request_data ) );
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $api_key,
			)
		);
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_WRITEFUNCTION, array( $this, 'stream_callback' ) );

		// Log cURL options for debugging.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info(
			'OpenAI API cURL Options: ' . wp_json_encode(
				array(
					'url'     => 'https://api.openai.com/v1/chat/completions',
					'timeout' => $timeout,
					'headers' => array(
						'Content-Type: application/json',
						'Authorization: Bearer ' . substr( $api_key, 0, 5 ) . '...',
					),
				)
			)
		);

		// Execute the cURL request.
		$response = curl_exec( $ch );

		// Check for cURL errors.
		if ( curl_errno( $ch ) ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'cURL Error: ' . curl_error( $ch ) );
			curl_close( $ch );
			throw new \Exception(
				sprintf(
				/* translators: %s: cURL error message */
					esc_html__( 'cURL error: %s', 'ubc-llm-chat' ),
					esc_html( curl_error( $ch ) )
				)
			);
		}

		// Close the cURL connection.
		curl_close( $ch );

		// Send a done event to signal the end of the stream.
		UBC_LLM_Chat_API_Utils::send_sse_event( 'done', array() );
	}

	/**
	 * Callback function for streaming responses.
	 *
	 * @since    1.0.0
	 * @param    resource $ch       The cURL handle.
	 * @param    string   $data     The data chunk.
	 * @return   integer            The length of the data processed.
	 */
	private function stream_callback( $ch, $data ) {
		// Check if the data is empty.
		if ( empty( $data ) ) {
			return strlen( $data );
		}

		// Log the raw data for debugging.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'OpenAI API Stream Data: ' . $data );

		// Split the data by lines.
		$lines = explode( "\n", $data );

		// Process each line.
		foreach ( $lines as $line ) {
			// Skip empty lines.
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			// Remove the "data: " prefix.
			if ( strpos( $line, 'data: ' ) === 0 ) {
				$line = substr( $line, 6 );
			}

			// Skip the [DONE] message.
			if ( trim( $line ) === '[DONE]' ) {
				continue;
			}

			// Try to decode the JSON.
			$json = json_decode( $line, true );

			// Skip if not valid JSON.
			if ( ! $json ) {
				continue;
			}

			// Check if this is a content delta.
			if ( isset( $json['choices'][0]['delta']['content'] ) ) {
				$content = $json['choices'][0]['delta']['content'];

				// Apply the message content filter to the chunk.
				// Note: For streaming, we can't provide the message index since it's not yet saved.
				// We use -1 to indicate it's a streaming chunk.
				$filtered_content = \UBC\LLMChat\UBC_LLM_Chat_Filters::filter_message_content(
					$content,
					'assistant',
					isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : '',
					-1
				);

				// Allow plugins to capture the content for processing.
				apply_filters( 'ubc_llm_chat_capture_content', $content );

				// Apply filter to allow plugins to stop sending the chunk to the client.
				// Return false from the filter to stop sending the chunk.
				$should_send = apply_filters( 'ubc_llm_chat_stream_chunk', true );

				if ( false !== $should_send ) {
					UBC_LLM_Chat_API_Utils::send_sse_event( 'message', array( 'content' => $filtered_content ) );
				}
			}
		}

		// Flush the output buffer.
		flush();

		// Return the length of the data.
		return strlen( $data );
	}
}
