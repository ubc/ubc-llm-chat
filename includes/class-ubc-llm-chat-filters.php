<?php
/**
 * The filter-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat
 * @subpackage UBC\LLMChat\Filters
 */

namespace UBC\LLMChat;

/**
 * The filter-specific functionality of the plugin.
 *
 * Defines methods for applying filters throughout the plugin.
 *
 * @package    UBC_LLM_Chat
 * @subpackage UBC_LLM_Chat/includes
 * @author     UBC
 */
class UBC_LLM_Chat_Filters {

	/**
	 * Filter the available LLM services.
	 *
	 * @since    1.0.0
	 * @param    array $services    The available LLM services.
	 * @return   array              The filtered LLM services.
	 */
	public static function filter_available_llm_services( $services ) {
		return apply_filters( 'ubc_llm_chat_available_llm_services', $services );
	}

	/**
	 * Filter the available models for a service.
	 *
	 * @since    1.0.0
	 * @param    array  $models     The available models.
	 * @param    string $service    The LLM service.
	 * @return   array              The filtered models.
	 */
	public static function filter_available_models( $models, $service ) {
		return apply_filters( 'ubc_llm_chat_available_models', $models, $service );
	}

	/**
	 * Filter the parameters for a specific model.
	 *
	 * @since    1.0.0
	 * @param    array  $parameters    The model parameters.
	 * @param    string $service       The LLM service.
	 * @param    string $model         The model name.
	 * @return   array                 The filtered parameters.
	 */
	public static function filter_model_parameters( $parameters, $service, $model ) {
		return apply_filters( 'ubc_llm_chat_model_parameters', $parameters, $service, $model );
	}

	/**
	 * Filter the rate limit for a user.
	 *
	 * @since    1.0.0
	 * @param    int     $rate_limit    The rate limit in seconds.
	 * @param    WP_User $user          The user object.
	 * @return   int                     The filtered rate limit.
	 */
	public static function filter_rate_limit( $rate_limit, $user ) {
		return apply_filters( 'ubc_llm_chat_rate_limit', $rate_limit, $user );
	}

	/**
	 * Filter the maximum number of conversations for a user.
	 *
	 * @since    1.0.0
	 * @param    int     $max_conversations    The maximum number of conversations.
	 * @param    WP_User $user                 The user object.
	 * @return   int                            The filtered maximum number of conversations.
	 */
	public static function filter_max_conversations( $max_conversations, $user ) {
		return apply_filters( 'ubc_llm_chat_max_conversations', $max_conversations, $user );
	}

	/**
	 * Filter the maximum number of messages per conversation.
	 *
	 * @since    1.0.0
	 * @param    int     $max_messages    The maximum number of messages.
	 * @param    WP_User $user            The user object.
	 * @param    string  $conversation_id The conversation ID.
	 * @return   int                       The filtered maximum number of messages.
	 */
	public static function filter_max_messages( $max_messages, $user, $conversation_id ) {
		return apply_filters( 'ubc_llm_chat_max_messages', $max_messages, $user, $conversation_id );
	}

	/**
	 * Filter whether a user can access the chat interface.
	 *
	 * @since    1.0.0
	 * @param    bool    $can_access    Whether the user can access the chat interface.
	 * @param    WP_User $user          The user object.
	 * @return   bool                    The filtered access status.
	 */
	public static function filter_user_can_access( $can_access, $user ) {
		return apply_filters( 'ubc_llm_chat_user_can_access', $can_access, $user );
	}

	/**
	 * Filter whether streaming is enabled for a specific instance.
	 *
	 * @since    1.0.0
	 * @param    bool   $streaming_enabled    Whether streaming is enabled.
	 * @param    string $instance_id          The instance ID.
	 * @return   bool                           The filtered streaming status.
	 */
	public static function filter_streaming_enabled( $streaming_enabled, $instance_id ) {
		return apply_filters( 'ubc_llm_chat_streaming_enabled', $streaming_enabled, $instance_id );
	}

	/**
	 * Filter the API request before it's sent.
	 *
	 * @since    1.0.0
	 * @param    array  $request    The API request.
	 * @param    string $service    The LLM service.
	 * @param    string $model      The model name.
	 * @return   array                The filtered API request.
	 */
	public static function filter_api_request( $request, $service, $model ) {
		return apply_filters( 'ubc_llm_chat_api_request', $request, $service, $model );
	}

	/**
	 * Filter the API response before it's returned.
	 *
	 * @since    1.0.0
	 * @param    mixed  $response    The API response.
	 * @param    string $service     The LLM service.
	 * @param    string $model       The model name.
	 * @return   mixed                 The filtered API response.
	 */
	public static function filter_api_response( $response, $service, $model ) {
		return apply_filters( 'ubc_llm_chat_api_response', $response, $service, $model );
	}

	/**
	 * Filter the message content before display.
	 *
	 * @since    1.0.0
	 * @param    string $content          The message content.
	 * @param    string $role             The message role (user or assistant).
	 * @param    string $conversation_id  The conversation ID.
	 * @param    int    $message_index    The message index in the conversation.
	 * @return   string                   The filtered message content.
	 */
	public static function filter_message_content( $content, $role, $conversation_id, $message_index ) {
		return apply_filters( 'ubc_llm_chat_message_content', $content, $role, $conversation_id, $message_index );
	}
}
