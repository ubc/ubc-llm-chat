/**
 * Message handling functions for UBC LLM Chat plugin.
 *
 * @package UBC\LLMChat
 */

(function(window, api, ui, utils, animation) {
    'use strict';

    /**
     * Message handling functions for the UBC LLM Chat plugin.
     */
    const UBCLLMChatMessages = {
        /**
         * Send a message to the current conversation.
         *
         * @param {string} message - The message to send.
         * @param {Object} instance - The chat instance.
         * @param {Function} createConversationCallback - Callback to create a new conversation.
         */
        sendMessage: function(message, instance, createConversationCallback) {
            console.log('sendMessage called with:', message);

            // Store the original message text to restore if needed
            const originalMessage = message;

            // Check if there is a current conversation
            if (!instance.state.currentConversationId) {
                console.log('No current conversation, creating a new one first');
                // Create a new conversation first
                if (createConversationCallback && typeof createConversationCallback === 'function') {
                    createConversationCallback();
                }

                // Save the message to send after the conversation is created
                instance.state.pendingMessage = message;
                return;
            }

            console.log('Current conversation ID:', instance.state.currentConversationId);

            // Show loading state
            instance.state.isLoading = true;

            // Prepare the user message data but don't render it yet
            const userMessageData = {
                role: 'user',
                content: message,
                timestamp: Math.floor(Date.now() / 1000)
            };

            console.log('Prepared user message data:', userMessageData);

            // Render a loading message
            const loadingMessageHTML = instance.templates.loading_message
                .replace('{{model}}', instance.llmModel || window.ubc_llm_chat_public.i18n.assistant);

            instance.elements.messages.insertAdjacentHTML('beforeend', loadingMessageHTML);

            // Store a reference to the loading message element
            const loadingMessageElement = instance.elements.messages.lastElementChild;

            // Scroll to the bottom
            instance.elements.chatArea.scrollTop = instance.elements.chatArea.scrollHeight;

            // Always use streaming by default
            console.log('Using streaming for message response');
            this.streamMessage(message, userMessageData, loadingMessageElement, instance, originalMessage);
        },

        /**
         * Stream a message response using Server-Sent Events (SSE).
         *
         * @param {string} message - The message to send.
         * @param {Object} userMessageData - The user message data.
         * @param {HTMLElement} loadingMessageElement - The loading message element.
         * @param {Object} instance - The chat instance.
         * @param {string} originalMessage - The original message text to restore if needed.
         */
        streamMessage: function(message, userMessageData, loadingMessageElement, instance, originalMessage) {
            console.log('Streaming message response for:', message);

            // Create notification callback
            const notificationCallback = (message, type) => {
                // Check if this is a countdown notification
                if (type === 'countdown' && typeof message === 'number') {
                    ui.showCountdown(message, instance.elements, () => {
                        console.log('Countdown complete, form enabled');
                    });
                } else {
                    // Regular notification
                    ui.showNotification(message, type, instance.elements, instance.templates);
                }
            };

            // Create a container for the assistant message
            let assistantMessageElement = null;
            let userMessageElement = null;
            let assistantMessageData = {
                role: 'assistant',
                content: '',
                timestamp: Math.floor(Date.now() / 1000)
            };

            // Create a unique ID for this message for animation tracking
            const messageId = `msg_${Date.now()}_${Math.floor(Math.random() * 1000)}`;

            // Handle chunk callback
            const onChunk = (data) => {
                // If this is the first chunk, we know the server accepted our message
                // So we can now render the user message and create the assistant message container
                if (!assistantMessageElement) {
                    console.log('First chunk received, rendering user message and creating assistant message element');

                    // First render the user message since the server accepted it
                    const userMessageHTML = ui.renderMessage(userMessageData, null, instance.templates);
                    instance.elements.messages.insertAdjacentHTML('beforeend', userMessageHTML);
                    userMessageElement = instance.elements.messages.lastElementChild;

                    // Remove the loading message
                    if (loadingMessageElement) {
                        loadingMessageElement.remove();
                    }

                    // Create the assistant message
                    const assistantMessageHTML = ui.renderMessage(assistantMessageData, null, instance.templates);
                    instance.elements.messages.insertAdjacentHTML('beforeend', assistantMessageHTML);
                    assistantMessageElement = instance.elements.messages.lastElementChild;

                    // Initialize animation for this message
                    animation.initMessageAnimation(messageId, assistantMessageElement);
                }

                // Append the content to the assistant message
                if (data.content) {
                    console.log('Adding content chunk:', data.content);
                    assistantMessageData.content += data.content;

                    // Add content to animation buffer
                    animation.addToBuffer(messageId, data.content);

                    // Scroll to the bottom
                    instance.elements.chatArea.scrollTop = instance.elements.chatArea.scrollHeight;
                }
            };

            // Handle complete callback
            const onComplete = (data) => {
                console.log('Streaming complete, final data:', data);

                // Create a final callback to update the conversation state after animation is complete
                const finalCallback = () => {
                    // Update the conversation in the state
                    if (data.conversation) {
                        const conversationIndex = instance.state.conversations.findIndex(c => c.id === instance.state.currentConversationId);
                        if (conversationIndex !== -1) {
                            instance.state.conversations[conversationIndex] = data.conversation;
                        }
                    }

                    // Update the conversation list
                    ui.renderConversations(
                        instance.state.conversations,
                        instance.elements,
                        instance.state.currentConversationId,
                        instance.templates
                    );

                    // Hide loading state
                    instance.state.isLoading = false;

                    console.log('Animation and streaming fully complete');
                };

                // Mark animation as complete but let it continue animating any remaining content
                animation.markAnimationComplete(messageId, finalCallback);
            };

            // Handle error callback
            const onError = (error) => {
                console.error('Streaming error:', error);

                // Remove the loading message if it still exists
                if (loadingMessageElement) {
                    loadingMessageElement.remove();
                }

                // Create a final callback to update the state after animation is complete
                const finalCallback = () => {
                    // Hide loading state
                    instance.state.isLoading = false;
                    console.log('Animation complete after error');

                    // Put the original message back in the textarea
                    if (instance.elements.input && originalMessage) {
                        console.log('Restoring original message to textarea:', originalMessage);
                        instance.elements.input.value = originalMessage;

                        // Trigger input event to resize textarea if needed
                        const inputEvent = new Event('input', { bubbles: true });
                        instance.elements.input.dispatchEvent(inputEvent);

                        // Focus the textarea
                        instance.elements.input.focus();
                    }
                };

                // Check if this is a rate limit error
                if (error && error.code === 'rate_limited' && error.remaining_time) {
                    console.log('Rate limit error detected, showing countdown for', error.remaining_time, 'seconds');

                    // Show the countdown in the UI
                    ui.showCountdown(error.remaining_time, instance.elements, () => {
                        console.log('Rate limit countdown complete');
                    });

                    // Show a rate limit notification instead of a generic error
                    notificationCallback(
                        error.message || `Rate limited. Please wait ${error.remaining_time} seconds before sending another message.`,
                        'warning'
                    );

                    // Call the final callback immediately
                    finalCallback();
                    return;
                }

                // For other errors, show an error notification
                notificationCallback(error.message || window.ubc_llm_chat_public.i18n.server_error, 'error');

                // If we already rendered the user message (unlikely for rate limit errors but possible for other errors)
                if (userMessageElement) {
                    // Remove the user message since it wasn't processed
                    userMessageElement.remove();
                }

                // If we already created an assistant message element
                if (assistantMessageElement) {
                    // Add an error note to the assistant message
                    const messageContentElement = assistantMessageElement.querySelector('.ubc-llm-chat-message-content');
                    if (messageContentElement) {
                        messageContentElement.innerHTML += '<p class="ubc-llm-chat-error-note">' +
                            (error.message || window.ubc_llm_chat_public.i18n.server_error) + '</p>';
                    }

                    // Mark animation as complete but let it continue animating any remaining content
                    animation.markAnimationComplete(messageId, finalCallback);
                } else {
                    // Call the callback immediately since there's no animation
                    finalCallback();
                }
            };

            // Create the streaming connection
            instance.state.eventSource = api.createStreamConnection(
                instance.state.currentConversationId,
                message,
                onChunk,
                onComplete,
                onError
            );
        }
    };

    // Expose the module
    window.UBCLLMChatMessages = UBCLLMChatMessages;
})(window, window.UBCLLMChatAPI, window.UBCLLMChatUI, window.UBCLLMChatUtils, window.UBCLLMChatAnimation);