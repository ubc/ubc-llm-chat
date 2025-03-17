<?php
/**
 * LLM Service Factory Class
 *
 * This class creates instances of LLM services based on the service name.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\API\Services
 */

namespace UBC\LLMChat\API\Services;

/**
 * LLM Service Factory Class
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_Service_Factory {

	/**
	 * Get an instance of the appropriate LLM service.
	 *
	 * @since    1.0.0
	 * @param    string $service_name    The name of the LLM service.
	 * @return   UBC_LLM_Chat_Service_Base    The LLM service instance.
	 * @throws   \Exception                   If the service is not supported.
	 */
	public static function get_service( $service_name ) {
		switch ( $service_name ) {
			case 'openai':
				return new UBC_LLM_Chat_OpenAI_Service();

			case 'ollama':
				return new UBC_LLM_Chat_Ollama_Service();

			case 'test':
				return new UBC_LLM_Chat_Test_Service();

			default:
				throw new \Exception(
					sprintf(
					/* translators: %s: service name */
						esc_html__( 'Unsupported LLM service: %s', 'ubc-llm-chat' ),
						esc_html( $service_name )
					)
				);
		}
	}
}
