# UBC LLM Chat

A WordPress plugin that creates a chat interface between the website and various large language models via their APIs.

## Description

UBC LLM Chat allows WordPress site administrators to integrate large language models (LLMs) like OpenAI's ChatGPT and Ollama into their websites. The plugin provides a chat interface that can be embedded on any post or page using a shortcode or block.

## Features

-   Admin settings interface for configuring LLM services
-   Support for OpenAI (ChatGPT) and Ollama
-   Shortcode for embedding the chat interface on posts and pages
-   WordPress block for embedding the chat interface using the block editor
-   Multiple chat conversations per user
-   Configurable rate limits and conversation limits
-   Streaming responses for real-time interaction

## Requirements

-   WordPress 5.8 or higher
-   PHP 8.0 or higher
-   OpenAI API key (for ChatGPT integration)
-   Ollama server (for Ollama integration)

## Installation

1. Upload the `ubc-llm-chat` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Settings > UBC LLM Chat' to configure the plugin

## Usage

### Shortcode

You can embed the chat interface on any post or page using the following shortcode:

```
[ubc_llm_chat llmservice="chatgpt" llm="gpt-4o-mini" minimum_user_role="subscriber" maxmessages="20" maxconversations="10" systemprompt="Always speak like a pirate" temperature="0.7" debug_mode="true"]
```

### Block

You can also embed the chat interface using the UBC LLM Chat block in the block editor.

## Configuration

### LLM Services

-   **OpenAI (ChatGPT)**: Enable OpenAI integration and provide your API key
-   **Ollama**: Enable Ollama integration and provide the URL to your Ollama server

### Global Settings

-   **Global Rate Limit**: Set the number of seconds between API requests
-   **Global Maximum Conversations**: Set the maximum number of conversations a user can have
-   **Global Maximum Messages**: Set the maximum number of messages per conversation
-   **Connection Timeout**: Set the connection timeout in seconds
-   **Minimum User Role**: Set the minimum user role required to access the chat interface
-   **Debug Mode**: Enable debug mode to output debug information to the log file

## Development

### Hooks

The plugin provides the following action hooks:

-   `ubc_llm_chat_settings_saved`: Fired after settings are saved
-   `ubc_llm_chat_api_connection_tested`: Fired after an API connection test
-   `ubc_llm_chat_models_fetched`: Fired after models are fetched from an API
-   `ubc_llm_chat_usage_updated`: Fired when usage statistics are updated

The plugin provides the following filter hooks:

-   `ubc_llm_chat_available_llm_services`: Filter the available LLM services
-   `ubc_llm_chat_available_models`: Filter the available models for a service
-   `ubc_llm_chat_model_parameters`: Filter the parameters for a specific model
-   `ubc_llm_chat_rate_limit`: Filter the rate limit for a user
-   `ubc_llm_chat_max_conversations`: Filter the maximum number of conversations for a user
-   `ubc_llm_chat_max_messages`: Filter the maximum number of messages per conversation
-   `ubc_llm_chat_user_can_access`: Filter whether a user can access the chat interface
-   `ubc_llm_chat_streaming_enabled`: Filter whether streaming is enabled for a specific instance
-   `ubc_llm_chat_api_request`: Filter the API request before it's sent
-   `ubc_llm_chat_api_response`: Filter the API response before it's returned

## License

This plugin is licensed under the GPL v2 or later.

## Credits

This plugin uses the [LLPhant](https://github.com/LLPhant/LLPhant) library for LLM integration.
