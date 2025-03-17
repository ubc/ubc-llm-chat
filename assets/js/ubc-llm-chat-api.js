/**
 * API functions for UBC LLM Chat plugin.
 *
 * @package UBC\LLMChat
 */

(function(window) {
    'use strict';

    /**
     * API functions for the UBC LLM Chat plugin.
     */
    const UBCLLMChatAPI = {
        /**
         * Make an API request.
         *
         * @param {string} endpoint - The API endpoint to request.
         * @param {Object} options - The request options.
         * @param {Function} notificationCallback - Function to show notifications.
         * @return {Promise} A promise that resolves with the response data.
         */
        apiRequest: function(endpoint, options = {}, notificationCallback = null) {
            console.log('apiRequest called with endpoint:', endpoint, 'options:', options);

            // Default options
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.ubc_llm_chat_public.nonce
                }
            };

            // Merge options
            const mergedOptions = { ...defaultOptions, ...options };

            // Convert body to JSON string if it exists
            if (mergedOptions.body) {
                mergedOptions.body = JSON.stringify(mergedOptions.body);
            }

            // Build the URL
            const url = window.ubc_llm_chat_public.rest_url + endpoint;
            console.log('Making request to URL:', url, 'with options:', mergedOptions);

            // Make the request
            return fetch(url, mergedOptions)
                .then(response => {
                    console.log('Received response:', response);

                    // Check if the response is ok
                    if (!response.ok) {
                        console.log('Response not OK, status:', response.status);

                        // Handle rate limiting
                        if (response.status === 429) {
                            // Try to parse the response to get the remaining time
                            return response.json().then(data => {
                                console.log('Rate limit data:', data);

                                // Get the remaining time from the response or headers
                                let remainingTime = 5; // Default to 5 seconds if not specified

                                if (data && data.data && data.data.remaining_time) {
                                    remainingTime = parseInt(data.data.remaining_time, 10);
                                } else {
                                    // Try to get from Retry-After header
                                    const retryAfter = response.headers.get('Retry-After');
                                    if (retryAfter) {
                                        remainingTime = parseInt(retryAfter, 10);
                                    }
                                }

                                // Show the countdown
                                if (notificationCallback) {
                                    // Use a special flag to indicate this is a countdown notification
                                    notificationCallback(remainingTime, 'countdown');
                                }

                                throw new Error('Rate limited');
                            }).catch(error => {
                                // If we can't parse the JSON, fall back to a generic message
                                if (error.message !== 'Rate limited' && notificationCallback) {
                                    notificationCallback(window.ubc_llm_chat_public.i18n.rate_limited, 'warning');
                                }
                                throw new Error('Rate limited');
                            });
                        }

                        // Handle server errors
                        if (response.status >= 500) {
                            if (notificationCallback) {
                                notificationCallback(window.ubc_llm_chat_public.i18n.server_error, 'error');
                            }
                            throw new Error('Server error');
                        }

                        // Handle other errors
                        return response.json().then(data => {
                            console.log('Error response data:', data);
                            const message = data.message || window.ubc_llm_chat_public.i18n.unknown_error;
                            if (notificationCallback) {
                                notificationCallback(message, 'error');
                            }
                            throw new Error(message);
                        });
                    }

                    // Return the response as JSON
                    return response.json();
                })
                .then(data => {
                    console.log('Parsed response data:', data);
                    return data;
                })
                .catch(error => {
                    console.error('API request error:', error);

                    // Handle network errors
                    if (error.name === 'TypeError') {
                        if (notificationCallback) {
                            notificationCallback(window.ubc_llm_chat_public.i18n.network_error, 'error');
                        }
                    }

                    // Re-throw the error
                    throw error;
                });
        },

        /**
         * Create a streaming connection for message responses.
         *
         * @param {string} conversationId - The ID of the conversation.
         * @param {string} message - The message to send.
         * @param {Function} onChunk - Callback for each chunk of data.
         * @param {Function} onComplete - Callback when streaming is complete.
         * @param {Function} onError - Callback for errors.
         */
        createStreamConnection: function(conversationId, message, onChunk, onComplete, onError) {
            // Create a new EventSource connection directly to the streaming endpoint
            const eventSourceUrl = `${window.ubc_llm_chat_public.rest_url}/conversations/${conversationId}/stream?content=${encodeURIComponent(message)}&_wpnonce=${window.ubc_llm_chat_public.nonce}`;

            // Only add test_mode parameter if it's true
            const testMode = window.ubc_llm_chat_public.test_mode === 'true' ? '&test_mode=true' : '';
            const fullUrl = eventSourceUrl + testMode;

            console.log('Creating EventSource with URL:', fullUrl);
            const eventSource = new EventSource(fullUrl);

            // Handle connection open
            eventSource.addEventListener('open', (event) => {
                console.log('SSE connection opened:', event);
            });

            // Handle message events
            eventSource.addEventListener('message', (event) => {
                try {
                    console.log('Raw message event data:', event.data);
                    const data = JSON.parse(event.data);
                    console.log('Received message chunk:', data);

                    // Call the chunk callback
                    if (onChunk && typeof onChunk === 'function') {
                        onChunk(data);
                    }
                } catch (error) {
                    console.error('Error parsing message event:', error, 'Raw data:', event.data);
                }
            });

            // Handle done event
            eventSource.addEventListener('done', (event) => {
                console.log('Received done event');
                try {
                    console.log('Raw done event data:', event.data);
                    const data = JSON.parse(event.data);
                    console.log('Streaming complete, final data:', data);

                    // Call the complete callback
                    if (onComplete && typeof onComplete === 'function') {
                        onComplete(data);
                    }

                    // Close the connection
                    eventSource.close();
                } catch (error) {
                    console.error('Error parsing done event:', error, 'Raw data:', event.data);
                    eventSource.close();

                    // Call the error callback
                    if (onError && typeof onError === 'function') {
                        onError(error);
                    }
                }
            });

            // Handle regular error events
            eventSource.addEventListener('error', (event) => {
                console.error('SSE error:', event);

                // Close the connection
                eventSource.close();

                // Check if this is a rate limit error (HTTP 429)
                // We need to extract the error information from the response headers
                // or try to parse the error from the URL
                const url = eventSource.url;

                // Try to extract rate limit information from the URL or response
                if (url && url.includes('/wp-json/')) {
                    // Make a fetch request to get the error details
                    fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-WP-Nonce': window.ubc_llm_chat_public.nonce
                        }
                    })
                    .then(response => {
                        if (response.status === 429) {
                            // Get the retry-after header
                            const retryAfter = response.headers.get('Retry-After');
                            let remainingTime = 15; // Default to 15 seconds

                            if (retryAfter) {
                                remainingTime = parseInt(retryAfter, 10);
                            }

                            // Call the error callback with rate limit data
                            if (onError && typeof onError === 'function') {
                                onError({
                                    message: `Rate limited. Please wait ${remainingTime} seconds before sending another message.`,
                                    code: 'rate_limited',
                                    remaining_time: remainingTime
                                });
                            }
                            return;
                        }

                        // For other errors, try to parse the response
                        return response.json().catch(() => null);
                    })
                    .then(data => {
                        if (data && data.code === 'rate_limited' && data.data && data.data.remaining_time) {
                            // Call the error callback with rate limit data
                            if (onError && typeof onError === 'function') {
                                onError({
                                    message: data.message,
                                    code: 'rate_limited',
                                    remaining_time: data.data.remaining_time
                                });
                            }
                        } else if (onError && typeof onError === 'function') {
                            // Call the error callback with a generic error
                            onError({ message: window.ubc_llm_chat_public.i18n.server_error });
                        }
                    })
                    .catch(() => {
                        // If all else fails, call the error callback with a generic error
                        if (onError && typeof onError === 'function') {
                            onError({ message: window.ubc_llm_chat_public.i18n.server_error });
                        }
                    });
                } else {
                    // Call the error callback with a generic error
                    if (onError && typeof onError === 'function') {
                        onError({ message: window.ubc_llm_chat_public.i18n.server_error });
                    }
                }
            });

            // Handle specific error events from the server
            eventSource.addEventListener('error_event', (event) => {
                console.error('Server error event:', event);
                try {
                    const data = JSON.parse(event.data);
                    console.error('Server error details:', data);

                    // Check if this is a rate limit error
                    if (data.code === 'rate_limited' && data.remaining_time) {
                        // Call the error callback with special rate limit data
                        if (onError && typeof onError === 'function') {
                            onError({
                                message: data.message,
                                code: 'rate_limited',
                                remaining_time: data.remaining_time
                            });
                        }
                    } else {
                        // Call the error callback with the server error message
                        if (onError && typeof onError === 'function') {
                            onError(data);
                        }
                    }
                } catch (error) {
                    console.error('Error parsing error event:', error);

                    // Call the error callback with a generic error
                    if (onError && typeof onError === 'function') {
                        onError({ message: window.ubc_llm_chat_public.i18n.server_error });
                    }
                }

                // Close the connection
                eventSource.close();
            });

            // Return the event source so it can be closed if needed
            return eventSource;
        }
    };

    // Expose the module
    window.UBCLLMChatAPI = UBCLLMChatAPI;
})(window);