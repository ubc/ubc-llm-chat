<?php
/**
 * Conversation Controller Class
 *
 * This class handles conversation-related API endpoints.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API
 */

namespace UBC\LLMChat\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use UBC\LLMChat\UBC_LLM_Chat_Filters;

/**
 * Conversation Controller Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_Conversation_Controller {

	/**
	 * Get a list of the user's conversations.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 */
	public function get_conversations( $request ) {
		// Get the current user ID.
		$user_id = get_current_user_id();

		// Get all user meta keys that match our conversation pattern.
		global $wpdb;
		$meta_keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_key FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
				$user_id,
				'ubc_llm_chat_conversation_%'
			)
		);

		// Initialize conversations array.
		$conversations = array();

		// Loop through meta keys and get conversation data.
		foreach ( $meta_keys as $meta_key ) {
			// Get conversation data.
			$conversation = get_user_meta( $user_id, $meta_key, true );

			// Skip if not an array or if marked as deleted (unless include_deleted is true).
			if ( ! is_array( $conversation ) ) {
				continue;
			}

			// Check if we should include deleted conversations.
			$include_deleted = isset( $request['include_deleted'] ) && $request['include_deleted'];

			if ( isset( $conversation['deleted'] ) && $conversation['deleted'] && ! $include_deleted ) {
				continue;
			}

			// Add to conversations array with minimal data.
			$conversations[] = array(
				'id'            => $conversation['id'],
				'title'         => $conversation['title'],
				'created'       => $conversation['created'],
				'updated'       => isset( $conversation['updated'] ) ? $conversation['updated'] : $conversation['created'],
				'deleted'       => isset( $conversation['deleted'] ) ? $conversation['deleted'] : false,
				'llm_service'   => isset( $conversation['llm_service'] ) ? $conversation['llm_service'] : '',
				'llm_model'     => isset( $conversation['llm_model'] ) ? $conversation['llm_model'] : '',
				'message_count' => isset( $conversation['messages'] ) ? count( $conversation['messages'] ) : 0,
			);
		}

		// Sort conversations by updated timestamp (newest first).
		usort(
			$conversations,
			function ( $a, $b ) {
				return $b['updated'] - $a['updated'];
			}
		);

		// Apply filter to allow plugins to modify the conversations list.
		$conversations = apply_filters( 'ubc_llm_chat_conversations_list', $conversations, $user_id );

		// Return the conversations.
		return new WP_REST_Response( $conversations, 200 );
	}

	/**
	 * Create a new conversation.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 */
	public function create_conversation( $request ) {
		// Get the current user ID.
		$user_id = get_current_user_id();

		// Get plugin settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Get the maximum number of conversations.
		$max_conversations = isset( $settings['global_max_conversations'] ) ? $settings['global_max_conversations'] : 10;

		// Check if the user has reached their conversation limit.
		if ( UBC_LLM_Chat_API_Utils::has_reached_conversation_limit( $user_id, $max_conversations ) ) {
			return new WP_Error(
				'conversation_limit_reached',
				esc_html__( 'You have reached the maximum number of conversations allowed.', 'ubc-llm-chat' ),
				array( 'status' => 403 )
			);
		}

		// Get the LLM service and model from the request.
		$llm_service = sanitize_text_field( $request['llm_service'] );
		$llm_model   = sanitize_text_field( $request['llm_model'] );

		// Check if the LLM service is enabled.
		$service_enabled = false;

		if ( 'openai' === $llm_service && isset( $settings['openai_enabled'] ) && $settings['openai_enabled'] ) {
			$service_enabled = true;
		} elseif ( 'ollama' === $llm_service && isset( $settings['ollama_enabled'] ) && $settings['ollama_enabled'] ) {
			$service_enabled = true;
		}

		// For testing purposes, allow empty LLM service or test_mode parameter.
		$is_test_mode = isset( $request['test_mode'] ) && 'true' === $request['test_mode'];
		if ( empty( $llm_service ) || $is_test_mode ) {
			$service_enabled = true;
			$llm_service     = 'test';
			$llm_model       = 'test_model';
		}

		if ( ! $service_enabled ) {
			return new WP_Error(
				'invalid_llm_service',
				esc_html__( 'The specified LLM service is not enabled.', 'ubc-llm-chat' ),
				array( 'status' => 400 )
			);
		}

		// Get the system prompt from the request.
		$system_prompt = isset( $request['system_prompt'] ) ? sanitize_textarea_field( $request['system_prompt'] ) : '';

		// Get the temperature from the request.
		$temperature = isset( $request['temperature'] ) ? (float) $request['temperature'] : 0.7;

		// Ensure temperature is between 0 and 1.
		$temperature = max( 0, min( 1, $temperature ) );

		// Generate a UUID for the conversation.
		$conversation_id = UBC_LLM_Chat_API_Utils::generate_uuid();

		// Create the conversation data.
		$conversation = array(
			'id'            => $conversation_id,
			'title'         => esc_html__( 'New Conversation', 'ubc-llm-chat' ),
			'created'       => time(),
			'updated'       => time(),
			'deleted'       => false,
			'llm_service'   => $llm_service,
			'llm_model'     => $llm_model,
			'system_prompt' => $system_prompt,
			'temperature'   => $temperature,
			'messages'      => array(),
		);

		// Get the meta key for the conversation.
		$meta_key = UBC_LLM_Chat_API_Utils::get_conversation_meta_key( $conversation_id );

		// Save the conversation.
		$result = update_user_meta( $user_id, $meta_key, $conversation );

		if ( ! $result ) {
			return new WP_Error(
				'conversation_creation_failed',
				esc_html__( 'Failed to create the conversation.', 'ubc-llm-chat' ),
				array( 'status' => 500 )
			);
		}

		// Fire action for conversation created.
		do_action( 'ubc_llm_chat_conversation_created', $conversation, $user_id );

		// Return the conversation data.
		return new WP_REST_Response( $conversation, 201 );
	}

	/**
	 * Get a specific conversation with its messages.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 */
	public function get_conversation( $request ) {
		// Get the conversation ID from the request.
		$conversation_id = sanitize_text_field( $request['id'] );

		// Get the current user ID.
		$user_id = get_current_user_id();

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

		// Apply message content filter to each message.
		if ( isset( $conversation['messages'] ) && is_array( $conversation['messages'] ) ) {
			foreach ( $conversation['messages'] as $index => $message ) {
				if ( isset( $message['content'] ) && isset( $message['role'] ) ) {
					$conversation['messages'][ $index ]['content'] = \UBC\LLMChat\UBC_LLM_Chat_Filters::filter_message_content(
						$message['content'],
						$message['role'],
						$conversation_id,
						$index
					);
				}
			}
		}

		// Apply filter to allow plugins to modify the conversation data.
		$conversation = apply_filters( 'ubc_llm_chat_conversation_data', $conversation, $user_id );

		// Return the conversation data.
		return new WP_REST_Response( $conversation, 200 );
	}

	/**
	 * Update a conversation (e.g., rename).
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 */
	public function update_conversation( $request ) {
		// Get the conversation ID from the request.
		$conversation_id = sanitize_text_field( $request['id'] );

		// Get the current user ID.
		$user_id = get_current_user_id();

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

		// Get the new title from the request.
		if ( isset( $request['title'] ) ) {
			$conversation['title'] = sanitize_text_field( $request['title'] );
		}

		// Get the new system prompt from the request.
		if ( isset( $request['system_prompt'] ) ) {
			$conversation['system_prompt'] = sanitize_textarea_field( $request['system_prompt'] );
		}

		// Get the new temperature from the request.
		if ( isset( $request['temperature'] ) ) {
			$temperature = (float) $request['temperature'];
			// Ensure temperature is between 0 and 1.
			$conversation['temperature'] = max( 0, min( 1, $temperature ) );
		}

		// Update the updated timestamp.
		$conversation['updated'] = time();

		// Apply filter to allow plugins to modify the conversation data before saving.
		$conversation = apply_filters( 'ubc_llm_chat_update_conversation', $conversation, $user_id, $request );

		// Save the updated conversation.
		$result = update_user_meta( $user_id, $meta_key, $conversation );

		if ( ! $result ) {
			return new WP_Error(
				'conversation_update_failed',
				esc_html__( 'Failed to update the conversation.', 'ubc-llm-chat' ),
				array( 'status' => 500 )
			);
		}

		// Fire action for conversation updated.
		do_action( 'ubc_llm_chat_conversation_updated', $conversation, $user_id );

		// Return the updated conversation data.
		return new WP_REST_Response( $conversation, 200 );
	}

	/**
	 * Delete a conversation (mark as deleted).
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 */
	public function delete_conversation( $request ) {
		// Get the conversation ID from the request.
		$conversation_id = sanitize_text_field( $request['id'] );

		// Get the current user ID.
		$user_id = get_current_user_id();

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

		// Check if the conversation is already deleted.
		if ( isset( $conversation['deleted'] ) && $conversation['deleted'] ) {
			return new WP_REST_Response(
				array(
					'message' => esc_html__( 'Conversation already deleted.', 'ubc-llm-chat' ),
				),
				200
			);
		}

		// Mark the conversation as deleted.
		$conversation['deleted'] = true;
		$conversation['updated'] = time();

		// Apply filter to allow plugins to modify the conversation data before saving.
		$conversation = apply_filters( 'ubc_llm_chat_delete_conversation', $conversation, $user_id );

		// Save the updated conversation.
		$result = update_user_meta( $user_id, $meta_key, $conversation );

		if ( ! $result ) {
			return new WP_Error(
				'conversation_deletion_failed',
				esc_html__( 'Failed to delete the conversation.', 'ubc-llm-chat' ),
				array( 'status' => 500 )
			);
		}

		// Fire action for conversation deleted.
		do_action( 'ubc_llm_chat_conversation_deleted', $conversation, $user_id );

		// Return success message.
		return new WP_REST_Response(
			array(
				'message' => esc_html__( 'Conversation deleted successfully.', 'ubc-llm-chat' ),
			),
			200
		);
	}

	/**
	 * Export a conversation as markdown.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 */
	public function export_conversation( $request ) {
		// Get the conversation ID from the request.
		$conversation_id = sanitize_text_field( $request['id'] );

		// Get the current user ID.
		$user_id = get_current_user_id();

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

		// Format the conversation as markdown.
		$markdown = UBC_LLM_Chat_API_Utils::format_conversation_for_export( $conversation );

		// Set the response headers for file download.
		$filename = sanitize_file_name( $conversation['title'] ) . '-' . gmdate( 'Y-m-d' ) . '.md';

		// Return the markdown content.
		$response = new WP_REST_Response(
			array(
				'content'  => $markdown,
				'filename' => $filename,
			),
			200
		);

		// Fire action for conversation exported.
		do_action( 'ubc_llm_chat_conversation_exported', $conversation, $user_id );

		return $response;
	}
}
