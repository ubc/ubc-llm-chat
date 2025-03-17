<?php
/**
 * The debug-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat
 * @subpackage UBC\LLMChat\Debug
 */

namespace UBC\LLMChat;

/**
 * The debug-specific functionality of the plugin.
 *
 * @link       https://ubc.ca
 * @since      1.0.0
 *
 * @package    UBC_LLM_Chat
 * @subpackage UBC_LLM_Chat/includes
 */

/**
 * The debug-specific functionality of the plugin.
 *
 * Defines methods for logging debug information to the WordPress debug log.
 *
 * @package    UBC_LLM_Chat
 * @subpackage UBC_LLM_Chat/includes
 * @author     UBC
 */
class UBC_LLM_Chat_Debug {

	/**
	 * Check if debug mode is enabled.
	 *
	 * @since    1.0.0
	 * @param    array $instance_settings    Optional. Instance-specific settings.
	 * @return   boolean                     True if debug mode is enabled, false otherwise.
	 */
	public static function is_debug_mode_enabled( $instance_settings = array() ) {
		// Check for instance-specific debug mode setting.
		if ( isset( $instance_settings['debug_mode'] ) ) {
			return (bool) $instance_settings['debug_mode'];
		}

		// Fall back to global setting.
		$settings = get_option( 'ubc_llm_chat_settings' );
		return isset( $settings['debug_mode'] ) && $settings['debug_mode'];
	}

	/**
	 * Log a message to the debug log if debug mode is enabled.
	 *
	 * @since    1.0.0
	 * @param    string $message            The message to log.
	 * @param    string $level              The log level (info, warning, error). Default is 'info'.
	 * @param    array  $instance_settings  Optional. Instance-specific settings.
	 * @return   void
	 */
	public static function log( $message, $level = 'info', $instance_settings = array() ) {
		if ( ! self::is_debug_mode_enabled( $instance_settings ) ) {
			return;
		}

		$timestamp         = current_time( 'mysql' );
		$plugin_name       = 'UBC LLM Chat';
		$formatted_message = "[{$timestamp}] [{$plugin_name}] [{$level}] {$message}" . PHP_EOL;

		// Write to wp-content/debug.log.
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';
		file_put_contents( $debug_log_path, $formatted_message, FILE_APPEND ); // phpcs:ignore
	}

	/**
	 * Log an info message.
	 *
	 * @since    1.0.0
	 * @param    string $message            The message to log.
	 * @param    array  $instance_settings  Optional. Instance-specific settings.
	 * @return   void
	 */
	public static function info( $message, $instance_settings = array() ) {
		self::log( $message, 'info', $instance_settings );
	}

	/**
	 * Log a warning message.
	 *
	 * @since    1.0.0
	 * @param    string $message            The message to log.
	 * @param    array  $instance_settings  Optional. Instance-specific settings.
	 * @return   void
	 */
	public static function warning( $message, $instance_settings = array() ) {
		self::log( $message, 'warning', $instance_settings );
	}

	/**
	 * Log an error message.
	 *
	 * @since    1.0.0
	 * @param    string $message            The message to log.
	 * @param    array  $instance_settings  Optional. Instance-specific settings.
	 * @return   void
	 */
	public static function error( $message, $instance_settings = array() ) {
		self::log( $message, 'error', $instance_settings );
	}

	/**
	 * Log an exception.
	 *
	 * @since    1.0.0
	 * @param    \Exception $exception         The exception to log.
	 * @param    string     $context           Additional context for the exception.
	 * @param    array      $instance_settings Optional. Instance-specific settings.
	 * @return   void
	 */
	public static function exception( $exception, $context = '', $instance_settings = array() ) {
		$message = "Exception: {$exception->getMessage()}";

		if ( ! empty( $context ) ) {
			$message .= " | Context: {$context}";
		}

		$message .= " | File: {$exception->getFile()} | Line: {$exception->getLine()}";

		self::error( $message, $instance_settings );
	}
}
