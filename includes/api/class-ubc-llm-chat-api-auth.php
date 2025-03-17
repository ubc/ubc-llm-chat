<?php
/**
 * API Authentication Class
 *
 * This class handles authentication and permission checking for the API.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API
 */

namespace UBC\LLMChat\API;

use WP_REST_Request;
use WP_Error;

/**
 * API Authentication Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_API_Auth {

	/**
	 * Check if the user has permission to access the API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   bool|WP_Error               True if the user has permission, WP_Error otherwise.
	 */
	public static function check_permission( $request ) {
		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You must be logged in to access this endpoint.', 'ubc-llm-chat' ),
				array( 'status' => 401 )
			);
		}

		// Get plugin settings.
		$settings = get_option( 'ubc_llm_chat_settings', array() );

		// Check if user has the required role.
		$minimum_role = isset( $settings['minimum_user_role'] ) ? $settings['minimum_user_role'] : 'subscriber';

		// Get the current user.
		$user = wp_get_current_user();

		// Check if user has the required role.
		$has_role = false;

		// Define role hierarchy.
		$roles_hierarchy = array(
			'subscriber'    => 0,
			'contributor'   => 1,
			'author'        => 2,
			'editor'        => 3,
			'administrator' => 4,
		);

		// Get the minimum role level.
		$minimum_role_level = isset( $roles_hierarchy[ $minimum_role ] ) ? $roles_hierarchy[ $minimum_role ] : 0;

		// Check if user has a role with sufficient permissions.
		foreach ( $user->roles as $role ) {
			if ( isset( $roles_hierarchy[ $role ] ) && $roles_hierarchy[ $role ] >= $minimum_role_level ) {
				$has_role = true;
				break;
			}
		}

		if ( ! $has_role ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have sufficient permissions to access this endpoint.', 'ubc-llm-chat' ),
				array( 'status' => 403 )
			);
		}

		// Allow plugins to filter the permission check.
		$has_permission = apply_filters( 'ubc_llm_chat_user_can_access', true, $user->ID );

		if ( ! $has_permission ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'Access to this endpoint has been restricted.', 'ubc-llm-chat' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
