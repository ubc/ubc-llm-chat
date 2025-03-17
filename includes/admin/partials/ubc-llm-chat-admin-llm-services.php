<?php
/**
 * Admin LLM Services tab content.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Admin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Register settings for this tab.
settings_fields( 'ubc_llm_chat_settings' );
do_settings_sections( 'ubc-llm-chat-llm-services' );
submit_button();
