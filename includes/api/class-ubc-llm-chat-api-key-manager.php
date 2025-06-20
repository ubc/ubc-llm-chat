<?php
/**
 * API Key Manager Class
 *
 * This class centralizes the encryption, decryption, and management of API keys
 * to ensure consistent handling throughout the plugin. This prevents issues where
 * encrypted keys might be used directly without decryption or where multiple
 * encryption/decryption implementations might diverge and cause compatibility issues.
 *
 * When working with API keys, always use this utility class instead of implementing
 * custom encryption/decryption logic.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API
 */

namespace UBC\LLMChat\API;

/**
 * API Key Manager Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_API_Key_Manager {

	/**
	 * Encrypt an API key for secure storage.
	 *
	 * @since    1.0.0
	 * @param    string $api_key    The API key to encrypt.
	 * @return   string             The encrypted API key.
	 */
	public static function encrypt_api_key( $api_key ) {
		$salt = wp_salt( 'auth' );
		return base64_encode( openssl_encrypt( $api_key, 'AES-256-CBC', $salt, 0, substr( $salt, 0, 16 ) ) );
	}

	/**
	 * Decrypt an API key for use in API requests.
	 *
	 * @since    1.0.0
	 * @param    string $encrypted_api_key    The encrypted API key.
	 * @return   string                       The decrypted API key.
	 */
	public static function decrypt_api_key( $encrypted_api_key ) {
		if ( empty( $encrypted_api_key ) ) {
			return '';
		}

		$salt = wp_salt( 'auth' );
		return openssl_decrypt( base64_decode( $encrypted_api_key ), 'AES-256-CBC', $salt, 0, substr( $salt, 0, 16 ) );
	}

	/**
	 * Get the decrypted API key for a specific service.
	 *
	 * @since    1.0.0
	 * @param    string $service    The service name (e.g., 'openai', 'ollama').
	 * @return   string             The decrypted API key or empty string if not set.
	 */
	public static function get_decrypted_api_key( $service ) {
		$settings = get_option( 'ubc_llm_chat_settings', array() );
		$key_name = $service . '_api_key';

		if ( ! isset( $settings[ $key_name ] ) || empty( $settings[ $key_name ] ) ) {
			return '';
		}

		return self::decrypt_api_key( $settings[ $key_name ] );
	}

	/**
	 * Update the API key for a specific service.
	 *
	 * @since    1.0.0
	 * @param    string $service    The service name (e.g., 'openai', 'ollama').
	 * @param    string $api_key    The new API key to encrypt and store.
	 * @return   bool               Whether the update was successful.
	 */
	public static function update_api_key( $service, $api_key ) {
		$settings = get_option( 'ubc_llm_chat_settings', array() );
		$key_name = $service . '_api_key';

		if ( empty( $api_key ) ) {
			$settings[ $key_name ] = '';
		} else {
			$settings[ $key_name ] = self::encrypt_api_key( $api_key );
		}

		return update_option( 'ubc_llm_chat_settings', $settings );
	}
}
