<?php
/**
 * Base LLM Service Class
 *
 * This abstract class defines the interface for LLM services.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API\Services
 */

namespace UBC\LLMChat\API\Services;

/**
 * Base LLM Service Class
 *
 * @since      1.0.0
 */
abstract class UBC_LLM_Chat_Service_Base {

	/**
	 * Get a response from the LLM service.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The model to use.
	 * @param    string  $system_prompt   The system prompt.
	 * @param    float   $temperature     The temperature setting.
	 * @param    integer $timeout         The connection timeout in seconds.
	 * @return   string                   The response content.
	 * @throws   \Exception               If there is an error with the API request.
	 */
	abstract public function get_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout );

	/**
	 * Stream a response from the LLM service.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The model to use.
	 * @param    string  $system_prompt   The system prompt.
	 * @param    float   $temperature     The temperature setting.
	 * @param    integer $timeout         The connection timeout in seconds.
	 * @return   void
	 * @throws   \Exception               If there is an error with the API request.
	 */
	abstract public function stream_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout );

	/**
	 * Prepare messages array from conversation history.
	 *
	 * @since    1.0.0
	 * @param    array  $conversation     The conversation data.
	 * @param    string $content          The user message content.
	 * @param    string $system_prompt    The system prompt.
	 * @return   array                    The prepared messages array.
	 */
	protected function prepare_messages( $conversation, $content, $system_prompt ) {
		// Prepare the messages array.
		$messages = array();

		// Add the system message if provided.
		if ( ! empty( $system_prompt ) ) {
			$messages[] = array(
				'role'    => 'system',
				'content' => $system_prompt,
			);
		}

		// Add previous messages from the conversation (up to the last 10).
		$should_add_user_message = true;
		if ( isset( $conversation['messages'] ) && is_array( $conversation['messages'] ) ) {
			$prev_messages = array_slice( $conversation['messages'], -10 );

			// Check if the last message is already the user's message with the same content.
			$last_message = end( $prev_messages );
			if ( $last_message &&
				isset( $last_message['role'] ) &&
				'user' === $last_message['role'] &&
				isset( $last_message['content'] ) &&
				$content === $last_message['content'] ) {
				$should_add_user_message = false;
				// Log that we're skipping the duplicate message.
				if ( class_exists( '\UBC\LLMChat\UBC_LLM_Chat_Debug' ) ) {
					\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Skipping duplicate user message: ' . $content );
				}
			}

			foreach ( $prev_messages as $msg ) {
				$messages[] = array(
					'role'    => $msg['role'],
					'content' => $msg['content'],
				);
			}
		}

		// Add the current user message only if it's not already the last message in the conversation.
		if ( $should_add_user_message ) {
			$messages[] = array(
				'role'    => 'user',
				'content' => $content,
			);
			// Log that we're adding the user message.
			if ( class_exists( '\UBC\LLMChat\UBC_LLM_Chat_Debug' ) ) {
				\UBC\LLMChat\UBC_LLM_Chat_Debug::info( 'Adding user message: ' . $content );
			}
		}

		return $messages;
	}
}
