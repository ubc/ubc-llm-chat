<?php
/**
 * Ollama Service Class
 *
 * This class handles interactions with the Ollama API.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API\Services
 */

namespace UBC\LLMChat\API\Services;

use UBC\LLMChat\API\UBC_LLM_Chat_API_Utils;
use UBC\LLMChat\UBC_LLM_Chat_Filters;
use LLPhant\OllamaConfig;
use LLPhant\Chat\OllamaChat;
use LLPhant\Chat\Message;
use LLPhant\Chat\Enums\ChatRole;
use LLPhant\Exception\HttpException;
use LLPhant\Exception\MissingParameterException;
use Psr\Http\Message\StreamInterface;

/**
 * Ollama Service Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_Ollama_Service extends UBC_LLM_Chat_Service_Base {

	/**
	 * Get a response from Ollama.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The Ollama model to use.
	 * @param    string  $system_prompt   The system prompt.
	 * @param    float   $temperature     The temperature setting.
	 * @param    integer $timeout         The connection timeout in seconds.
	 * @return   string                   The response content.
	 * @throws   \Exception               If there is an error with the API request.
	 */
	public function get_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout ) {
		try {
			// Get plugin settings.
			$settings = get_option( 'ubc_llm_chat_settings', array() );

			// Get the Ollama URL.
			$ollama_url = isset( $settings['ollama_url'] ) ? $settings['ollama_url'] : 'http://localhost:11434/api/';

			// Ensure the URL ends with a slash.
			if ( substr( $ollama_url, -1 ) !== '/' ) {
				$ollama_url .= '/';
			}

			// Log the full URL for debugging.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Full URL: ' . $ollama_url );

			// Create Ollama configuration.
			$config          = new OllamaConfig();
			$config->url     = $ollama_url;
			$config->model   = $model;
			$config->timeout = $timeout;
			$config->stream  = false;

			// Set temperature in model options - using the property as defined in the library.
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$config->modelOptions['temperature'] = (float) $temperature;
			// phpcs:enable

			// Initialize the Ollama chat client.
			$chat = new OllamaChat( $config );

			// Set system prompt if provided.
			if ( ! empty( $system_prompt ) ) {
				$chat->setSystemMessage( $system_prompt );
			}

			// Prepare the messages array.
			$messages         = $this->prepare_messages( $conversation, $content, '' ); // Empty system prompt as we set it directly.
			$llphant_messages = $this->convert_to_llphant_messages( $messages );

			// Log the request data for debugging.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Request Data: ' . wp_json_encode( $messages ) );

			// Generate the chat response.
			$response = $chat->generateChat( $llphant_messages );

			// Log the response for debugging.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Response: ' . $response );

			return $response;

		} catch ( MissingParameterException $e ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Ollama API Missing Parameter Error: ' . $e->getMessage() );
			throw new \Exception(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Configuration error: %s', 'ubc-llm-chat' ),
					esc_html( $e->getMessage() )
				)
			);
		} catch ( HttpException $e ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Ollama API HTTP Error: ' . $e->getMessage() );
			throw new \Exception(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'API error: %s', 'ubc-llm-chat' ),
					esc_html( $e->getMessage() )
				)
			);
		} catch ( \Exception $e ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Ollama API Error: ' . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Stream a response from Ollama.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The Ollama model to use.
	 * @param    string  $system_prompt   The system prompt.
	 * @param    float   $temperature     The temperature setting.
	 * @param    integer $timeout         The connection timeout in seconds.
	 * @return   void
	 * @throws   \Exception               If there is an error with the API request.
	 */
	public function stream_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout ) {
		// Make sure output buffering is off.
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Set headers for SSE if they haven't been sent yet.
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/event-stream' );
			header( 'Cache-Control: no-cache' );
			header( 'Connection: keep-alive' );
			header( 'X-Accel-Buffering: no' ); // Disable buffering in Nginx.
		}

		try {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Response Method Called - Content: ' . $content );
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream Response Method Called - Model: ' . $model );

			// Get plugin settings.
			$settings = get_option( 'ubc_llm_chat_settings', array() );

			// Get the Ollama URL.
			$ollama_url = isset( $settings['ollama_url'] ) ? $settings['ollama_url'] : 'http://localhost:11434/api/';

			// Ensure the URL ends with a slash.
			if ( substr( $ollama_url, -1 ) !== '/' ) {
				$ollama_url .= '/';
			}

			// Log the full URL for debugging.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Streaming Full URL: ' . $ollama_url );

			// Create Ollama configuration.
			$config          = new OllamaConfig();
			$config->url     = $ollama_url;
			$config->model   = $model;
			$config->timeout = $timeout;
			$config->stream  = true; // Enable streaming.

			// Set temperature in model options - using the property as defined in the library.
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$config->modelOptions['temperature'] = (float) $temperature;
			// phpcs:enable

			// Initialize the Ollama chat client.
			$chat = new OllamaChat( $config );

			// Set system prompt if provided.
			if ( ! empty( $system_prompt ) ) {
				$chat->setSystemMessage( $system_prompt );
			}

			// Prepare the messages array.
			$messages         = $this->prepare_messages( $conversation, $content, '' ); // Empty system prompt as we set it directly.
			$llphant_messages = $this->convert_to_llphant_messages( $messages );

			// Log the request data for debugging.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Streaming Request Data: ' . wp_json_encode( $messages ) );
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Streaming LLPhant Messages: ' . wp_json_encode( $llphant_messages ) );

			// Log the number of messages for debugging.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Streaming Message Count: ' . count( $messages ) );

			// Log each message role for debugging.
			$message_roles = array_map(
				function ( $msg ) {
					return $msg['role'];
				},
				$messages
			);
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Streaming Message Roles: ' . wp_json_encode( $message_roles ) );

			// Generate the chat stream.
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'About to call generateChatStream' );
			$stream = $chat->generateChatStream( $llphant_messages );
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Generated chat stream, about to process it' );

			// Process the stream.
			$this->process_stream( $stream );

			// Send a done event to signal the end of the stream.
			echo "event: done\n";
			echo "data: {}\n\n";
			flush();
			\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Stream response completed successfully' );

		} catch ( MissingParameterException $e ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Ollama API Streaming Missing Parameter Error: ' . $e->getMessage() );

			// Send error as SSE event.
			echo "event: error\n";
			echo 'data: ' . json_encode(
				array(
					'code'    => 'configuration_error',
					/* translators: %s: error message */
					'message' => sprintf( esc_html__( 'Configuration error: %s', 'ubc-llm-chat' ), esc_html( $e->getMessage() ) ),
					'status'  => 500,
				)
			) . "\n\n";
			flush();

		} catch ( HttpException $e ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Ollama API Streaming HTTP Error: ' . $e->getMessage() );

			// Send error as SSE event.
			echo "event: error\n";
			echo 'data: ' . json_encode(
				array(
					'code'    => 'api_error',
					/* translators: %s: error message */
					'message' => sprintf( esc_html__( 'API error: %s', 'ubc-llm-chat' ), esc_html( $e->getMessage() ) ),
					'status'  => 500,
				)
			) . "\n\n";
			flush();

		} catch ( \Exception $e ) {
			\UBC\LLMChat\UBC_LLM_Chat_Debug::error( 'Ollama API Streaming Error: ' . $e->getMessage() );

			// Send error as SSE event.
			echo "event: error\n";
			echo 'data: ' . json_encode(
				array(
					'code'    => 'general_error',
					/* translators: %s: error message */
					'message' => sprintf( esc_html__( 'Error: %s', 'ubc-llm-chat' ), esc_html( $e->getMessage() ) ),
					'status'  => 500,
				)
			) . "\n\n";
			flush();
		}
	}

	/**
	 * Process a stream from the LLPhant library.
	 *
	 * @since    1.0.0
	 * @param    StreamInterface $stream    The stream to process.
	 * @return   void
	 */
	private function process_stream( StreamInterface $stream ) {
		// Make sure output buffering is off and we're sending content immediately.
		if ( ob_get_level() ) {
			ob_end_clean(); // Use clean instead of flush to prevent output before headers.
		}

		// Disable time limit for long-running requests.
		set_time_limit( 0 );

		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Starting to process stream' );

		// Send a test message to ensure the connection is working.
		echo "event: message\n";
		echo 'data: ' . json_encode( array( 'content' => '' ) ) . "\n\n";
		flush();

		// Read the stream and send SSE events for each chunk.
		while ( ! $stream->eof() ) {
			// Read a small chunk at a time to ensure real-time streaming.
			$chunk = $stream->read( 128 ); // Smaller chunk size for more frequent updates.

			if ( ! empty( $chunk ) ) {
				// Log the chunk for debugging.
				\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Ollama API Stream Chunk: ' . $chunk );

				// Apply the message content filter to the chunk.
				// Note: For streaming, we can't provide the message index since it's not yet saved.
				// We use -1 to indicate it's a streaming chunk.
				$filtered_chunk = \UBC\LLMChat\UBC_LLM_Chat_Filters::filter_message_content(
					$chunk,
					'assistant',
					isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : '',
					-1
				);

				// Allow plugins to capture the content for processing.
				apply_filters( 'ubc_llm_chat_capture_content', $chunk );

				// Apply filter to allow plugins to stop sending the chunk to the client.
				// Return false from the filter to stop sending the chunk.
				$should_send = apply_filters( 'ubc_llm_chat_stream_chunk', true );

				if ( false !== $should_send ) {
					// Send the chunk as an SSE event.
					echo "event: message\n";
					echo 'data: ' . json_encode( array( 'content' => $filtered_chunk ) ) . "\n\n";

					// Force flush to ensure immediate delivery.
					flush();
				}

				// Small sleep to prevent overwhelming the client.
				usleep( 5000 ); // 5ms - reduced from 10ms.
			}
		}

		\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Finished processing stream' );
	}

	/**
	 * Convert WordPress-formatted messages to LLPhant Message objects.
	 *
	 * @since    1.0.0
	 * @param    array $messages        The WordPress-formatted messages.
	 * @return   array                   Array of LLPhant Message objects.
	 */
	private function convert_to_llphant_messages( $messages ) {
		$llphant_messages = array();

		foreach ( $messages as $message ) {
			switch ( $message['role'] ) {
				case 'system':
					$llphant_messages[] = Message::system( $message['content'] );
					break;
				case 'user':
					$llphant_messages[] = Message::user( $message['content'] );
					break;
				case 'assistant':
					$llphant_messages[] = Message::assistant( $message['content'] );
					break;
				default:
					// Skip unknown roles.
					break;
			}
		}

		return $llphant_messages;
	}
}
