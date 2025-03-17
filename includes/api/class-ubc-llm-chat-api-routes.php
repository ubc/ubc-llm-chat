<?php
/**
 * The API routes registration class.
 *
 * This class handles the registration of REST API routes for the UBC LLM Chat plugin.
 *
 * @link       https://ubc.ca
 * @since      1.0.0
 *
 * @package    UBC_LLM_Chat
 * @subpackage UBC_LLM_Chat/includes/api
 */

namespace UBC\LLMChat\API;

use WP_REST_Server;

/**
 * The API routes registration class.
 *
 * This class handles the registration of REST API routes for the UBC LLM Chat plugin.
 *
 * @since      1.0.0
 * @package    UBC_LLM_Chat
 * @subpackage UBC_LLM_Chat/includes/api
 * @author     Richard Tape <richard.tape@ubc.ca>
 */
class UBC_LLM_Chat_API_Routes {

	/**
	 * Register the REST API routes.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function register_routes() {
		// Create instances of the controllers.
		$conversation_controller = new UBC_LLM_Chat_Conversation_Controller();
		$message_controller      = new UBC_LLM_Chat_Message_Controller();

		// Define the namespace.
		$namespace = 'ubc-llm-chat/v1';

		// Register route for getting a list of conversations and creating a new conversation.
		register_rest_route(
			$namespace,
			'/conversations',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $conversation_controller, 'get_conversations' ),
					'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
					'args'                => array(
						'include_deleted' => array(
							'type'     => 'boolean',
							'required' => false,
							'default'  => false,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $conversation_controller, 'create_conversation' ),
					'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
					'args'                => array(
						'llm_service'   => array(
							'type'     => 'string',
							'required' => true,
						),
						'llm_model'     => array(
							'type'     => 'string',
							'required' => true,
						),
						'system_prompt' => array(
							'type'     => 'string',
							'required' => false,
						),
						'temperature'   => array(
							'type'     => 'number',
							'required' => false,
							'default'  => 0.7,
						),
						'test_mode'     => array(
							'type'     => 'string',
							'required' => false,
						),
					),
				),
			)
		);

		// Register route for getting, updating, or deleting a specific conversation.
		register_rest_route(
			$namespace,
			'/conversations/(?P<id>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $conversation_controller, 'get_conversation' ),
					'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $conversation_controller, 'update_conversation' ),
					'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
					'args'                => array(
						'title'         => array(
							'type'     => 'string',
							'required' => false,
						),
						'system_prompt' => array(
							'type'     => 'string',
							'required' => false,
						),
						'temperature'   => array(
							'type'     => 'number',
							'required' => false,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $conversation_controller, 'delete_conversation' ),
					'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
				),
			)
		);

		// Register route for adding a message to a conversation.
		register_rest_route(
			$namespace,
			'/conversations/(?P<id>[a-zA-Z0-9_-]+)/messages',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $message_controller, 'add_message' ),
				'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
				'args'                => array(
					'content'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'test_mode' => array(
						'type'     => 'string',
						'required' => false,
					),
				),
			)
		);

		// Register route for streaming a message response.
		register_rest_route(
			$namespace,
			'/conversations/(?P<id>[a-zA-Z0-9_-]+)/stream',
			array(
				'methods'             => WP_REST_Server::READABLE . ', ' . WP_REST_Server::CREATABLE,
				'callback'            => array( $message_controller, 'stream_message' ),
				'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
				'args'                => array(
					'content'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'test_mode' => array(
						'type'     => 'string',
						'required' => false,
					),
				),
			)
		);

		// Register route for exporting a conversation.
		register_rest_route(
			$namespace,
			'/conversations/(?P<id>[a-zA-Z0-9_-]+)/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $conversation_controller, 'export_conversation' ),
				'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
			)
		);

		// Register route for getting chat settings.
		register_rest_route(
			$namespace,
			'/settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $message_controller, 'get_settings' ),
				'permission_callback' => array( 'UBC\LLMChat\API\UBC_LLM_Chat_API_Auth', 'check_permission' ),
			)
		);
	}
}
