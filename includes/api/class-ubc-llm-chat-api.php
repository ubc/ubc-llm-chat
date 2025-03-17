<?php
/**
 * The API class.
 *
 * This class defines the REST API endpoints for the UBC LLM Chat plugin.
 *
 * @link       https://ubc.ca
 * @since      1.0.0
 *
 * @package    UBC_LLM_Chat
 * @subpackage UBC_LLM_Chat/includes/api
 */

namespace UBC\LLMChat\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Exception;

/**
 * The API class.
 *
 * This class defines the REST API endpoints for the UBC LLM Chat plugin.
 *
 * @since      1.0.0
 * @package    UBC_LLM_Chat
 * @subpackage UBC_LLM_Chat/includes/api
 * @author     Richard Tape <richard.tape@ubc.ca>
 */
class UBC_LLM_Chat_API {

	/**
	 * The API namespace.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $namespace    The API namespace.
	 */
	private $namespace = 'ubc-llm-chat/v1';

	/**
	 * The conversation controller.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      UBC_LLM_Chat_Conversation_Controller    $conversation_controller    The conversation controller.
	 */
	private $conversation_controller;

	/**
	 * The message controller.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      UBC_LLM_Chat_Message_Controller    $message_controller    The message controller.
	 */
	private $message_controller;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->conversation_controller = new UBC_LLM_Chat_Conversation_Controller();
		$this->message_controller      = new UBC_LLM_Chat_Message_Controller();
	}

	/**
	 * Register the REST API routes.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function register_routes() {
		// Use the routes registration class to register all routes.
		UBC_LLM_Chat_API_Routes::register_routes();
	}

	/**
	 * Check if the user has permission to access the API.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   bool|WP_Error               True if the user has permission, WP_Error otherwise.
	 * @deprecated Use UBC_LLM_Chat_API_Auth::check_permission() instead.
	 */
	public function check_permission( $request ) {
		return UBC_LLM_Chat_API_Auth::check_permission( $request );
	}

	/**
	 * Get a list of the user's conversations.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 * @deprecated Use UBC_LLM_Chat_Conversation_Controller::get_conversations() instead.
	 */
	public function get_conversations( $request ) {
		return $this->conversation_controller->get_conversations( $request );
	}

	/**
	 * Create a new conversation.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 * @deprecated Use UBC_LLM_Chat_Conversation_Controller::create_conversation() instead.
	 */
	public function create_conversation( $request ) {
		return $this->conversation_controller->create_conversation( $request );
	}

	/**
	 * Get a specific conversation with its messages.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 * @deprecated Use UBC_LLM_Chat_Conversation_Controller::get_conversation() instead.
	 */
	public function get_conversation( $request ) {
		return $this->conversation_controller->get_conversation( $request );
	}

	/**
	 * Update a conversation (e.g., rename).
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 * @deprecated Use UBC_LLM_Chat_Conversation_Controller::update_conversation() instead.
	 */
	public function update_conversation( $request ) {
		return $this->conversation_controller->update_conversation( $request );
	}

	/**
	 * Delete a conversation (mark as deleted).
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 * @deprecated Use UBC_LLM_Chat_Conversation_Controller::delete_conversation() instead.
	 */
	public function delete_conversation( $request ) {
		return $this->conversation_controller->delete_conversation( $request );
	}

	/**
	 * Add a message to a conversation.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 * @deprecated Use UBC_LLM_Chat_Message_Controller::add_message() instead.
	 */
	public function add_message( $request ) {
		return $this->message_controller->add_message( $request );
	}

	/**
	 * Export a conversation as markdown.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 * @deprecated Use UBC_LLM_Chat_Conversation_Controller::export_conversation() instead.
	 */
	public function export_conversation( $request ) {
		return $this->conversation_controller->export_conversation( $request );
	}

	/**
	 * Get chat settings (available LLMs, rate limits, etc.).
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response|WP_Error   The response object on success, or WP_Error on failure.
	 * @deprecated Use UBC_LLM_Chat_Message_Controller::get_settings() instead.
	 */
	public function get_settings( $request ) {
		return $this->message_controller->get_settings( $request );
	}

	/**
	 * Stream a message response.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   void
	 * @deprecated Use UBC_LLM_Chat_Message_Controller::stream_message() instead.
	 */
	public function stream_message( $request ) {
		$this->message_controller->stream_message( $request );
	}

	/**
	 * Stream a response from Ollama.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    array   $conversation    The conversation data.
	 * @param    string  $content         The user message content.
	 * @param    string  $model           The Ollama model to use.
	 * @param    string  $system_prompt   The system prompt.
	 * @param    float   $temperature     The temperature setting.
	 * @param    integer $timeout         The connection timeout in seconds.
	 * @return   void
	 * @deprecated Use UBC_LLM_Chat_Ollama_Service::stream_response() instead.
	 */
	private function stream_ollama_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout ) {
		$service = new \UBC\LLMChat\API\Services\UBC_LLM_Chat_Ollama_Service();
		$service->stream_response( $conversation, $content, $model, $system_prompt, $temperature, $timeout );
	}

	/**
	 * Send an SSE event.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    string $event    The event name.
	 * @param    array  $data     The event data.
	 * @return   void
	 * @deprecated Use UBC_LLM_Chat_API_Utils::send_sse_event() instead.
	 */
	private function send_sse_event( $event, $data ) {
		UBC_LLM_Chat_API_Utils::send_sse_event( $event, $data );
	}

	/**
	 * Send an SSE error event.
	 *
	 * This method is kept for backward compatibility.
	 *
	 * @since    1.0.0
	 * @param    string $code       The error code.
	 * @param    string $message    The error message.
	 * @param    int    $status     The HTTP status code.
	 * @return   void
	 * @deprecated Use UBC_LLM_Chat_API_Utils::send_sse_error() instead.
	 */
	private function send_sse_error( $code, $message, $status ) {
		UBC_LLM_Chat_API_Utils::send_sse_error( $code, $message, $status );
	}
}
