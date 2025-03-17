<?php
/**
 * The usage tracking functionality of the plugin.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat
 * @subpackage UBC\LLMChat\Usage
 */

namespace UBC\LLMChat;

/**
 * The usage tracking functionality of the plugin.
 *
 * Defines methods for tracking and updating usage statistics.
 *
 * @package    UBC_LLM_Chat
 * @subpackage UBC_LLM_Chat/includes
 * @author     UBC
 */
class UBC_LLM_Chat_Usage {

	/**
	 * Track a new conversation.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id          The user ID.
	 * @param    string $conversation_id  The conversation ID.
	 * @param    string $service          The LLM service.
	 * @param    string $model            The model name.
	 * @return   void
	 */
	public static function track_conversation( $user_id, $conversation_id, $service, $model ) {
		// Get current conversation count.
		$conversation_count = get_user_meta( $user_id, 'ubc_llm_chat_conversation_count', true );
		$conversation_count = empty( $conversation_count ) ? 0 : intval( $conversation_count );

		// Increment conversation count.
		++$conversation_count;

		// Update conversation count.
		update_user_meta( $user_id, 'ubc_llm_chat_conversation_count', $conversation_count );

		// Store conversation details.
		$conversations = get_user_meta( $user_id, 'ubc_llm_chat_conversations', true );
		$conversations = empty( $conversations ) ? array() : $conversations;

		$conversations[ $conversation_id ] = array(
			'service'    => $service,
			'model'      => $model,
			'created_at' => current_time( 'mysql' ),
			'messages'   => 0,
		);

		update_user_meta( $user_id, 'ubc_llm_chat_conversations', $conversations );

		// Fire action after usage is updated.
		do_action( 'ubc_llm_chat_usage_updated', $user_id, 'conversation', $conversation_id );
	}

	/**
	 * Track a new message.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id          The user ID.
	 * @param    string $conversation_id  The conversation ID.
	 * @param    string $message          The message content.
	 * @param    string $role             The message role (user or assistant).
	 * @return   void
	 */
	public static function track_message( $user_id, $conversation_id, $message, $role ) {
		// Get current message count.
		$message_count = get_user_meta( $user_id, 'ubc_llm_chat_message_count', true );
		$message_count = empty( $message_count ) ? 0 : intval( $message_count );

		// Increment message count.
		++$message_count;

		// Update message count.
		update_user_meta( $user_id, 'ubc_llm_chat_message_count', $message_count );

		// Update conversation message count.
		$conversations = get_user_meta( $user_id, 'ubc_llm_chat_conversations', true );
		if ( ! empty( $conversations ) && isset( $conversations[ $conversation_id ] ) ) {
			++$conversations[ $conversation_id ]['messages'];
			update_user_meta( $user_id, 'ubc_llm_chat_conversations', $conversations );
		}

		// Store message details.
		$messages = get_user_meta( $user_id, "ubc_llm_chat_messages_{$conversation_id}", true );
		$messages = empty( $messages ) ? array() : $messages;

		$messages[] = array(
			'content'    => $message,
			'role'       => $role,
			'created_at' => current_time( 'mysql' ),
		);

		update_user_meta( $user_id, "ubc_llm_chat_messages_{$conversation_id}", $messages );

		// Fire action after usage is updated.
		do_action( 'ubc_llm_chat_usage_updated', $user_id, 'message', $conversation_id );
	}

	/**
	 * Delete a conversation.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id          The user ID.
	 * @param    string $conversation_id  The conversation ID.
	 * @return   void
	 */
	public static function delete_conversation( $user_id, $conversation_id ) {
		// Get conversations.
		$conversations = get_user_meta( $user_id, 'ubc_llm_chat_conversations', true );
		if ( empty( $conversations ) || ! isset( $conversations[ $conversation_id ] ) ) {
			return;
		}

		// Mark conversation as deleted.
		$conversations[ $conversation_id ]['deleted']    = true;
		$conversations[ $conversation_id ]['deleted_at'] = current_time( 'mysql' );

		// Update conversations.
		update_user_meta( $user_id, 'ubc_llm_chat_conversations', $conversations );

		// Fire action after usage is updated.
		do_action( 'ubc_llm_chat_usage_updated', $user_id, 'conversation_deleted', $conversation_id );
	}

	/**
	 * Get user conversations.
	 *
	 * @since    1.0.0
	 * @param    int  $user_id    The user ID.
	 * @param    bool $include_deleted    Whether to include deleted conversations.
	 * @return   array                The user conversations.
	 */
	public static function get_user_conversations( $user_id, $include_deleted = false ) {
		$conversations = get_user_meta( $user_id, 'ubc_llm_chat_conversations', true );
		if ( empty( $conversations ) ) {
			return array();
		}

		if ( ! $include_deleted ) {
			foreach ( $conversations as $id => $conversation ) {
				if ( isset( $conversation['deleted'] ) && $conversation['deleted'] ) {
					unset( $conversations[ $id ] );
				}
			}
		}

		return $conversations;
	}

	/**
	 * Get conversation messages.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id          The user ID.
	 * @param    string $conversation_id  The conversation ID.
	 * @return   array                      The conversation messages.
	 */
	public static function get_conversation_messages( $user_id, $conversation_id ) {
		$messages = get_user_meta( $user_id, "ubc_llm_chat_messages_{$conversation_id}", true );
		return empty( $messages ) ? array() : $messages;
	}

	/**
	 * Check if a user has reached the maximum number of conversations.
	 *
	 * @since    1.0.0
	 * @param    int $user_id    The user ID.
	 * @return   bool                 Whether the user has reached the maximum number of conversations.
	 */
	public static function has_reached_max_conversations( $user_id ) {
		// Get settings.
		$settings          = get_option( 'ubc_llm_chat_settings' );
		$max_conversations = isset( $settings['global_max_conversations'] ) ? intval( $settings['global_max_conversations'] ) : 10;

		// Apply filter.
		$max_conversations = UBC_LLM_Chat_Filters::filter_max_conversations( $max_conversations, get_user_by( 'id', $user_id ) );

		// Get conversation count.
		$conversation_count = get_user_meta( $user_id, 'ubc_llm_chat_conversation_count', true );
		$conversation_count = empty( $conversation_count ) ? 0 : intval( $conversation_count );

		return $conversation_count >= $max_conversations;
	}

	/**
	 * Check if a conversation has reached the maximum number of messages.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id          The user ID.
	 * @param    string $conversation_id  The conversation ID.
	 * @return   bool                       Whether the conversation has reached the maximum number of messages.
	 */
	public static function has_reached_max_messages( $user_id, $conversation_id ) {
		// Get settings.
		$settings     = get_option( 'ubc_llm_chat_settings' );
		$max_messages = isset( $settings['global_max_messages'] ) ? intval( $settings['global_max_messages'] ) : 20;

		// Apply filter.
		$max_messages = UBC_LLM_Chat_Filters::filter_max_messages( $max_messages, get_user_by( 'id', $user_id ), $conversation_id );

		// Get conversation.
		$conversations = get_user_meta( $user_id, 'ubc_llm_chat_conversations', true );
		if ( empty( $conversations ) || ! isset( $conversations[ $conversation_id ] ) ) {
			return false;
		}

		return $conversations[ $conversation_id ]['messages'] >= $max_messages;
	}
}
