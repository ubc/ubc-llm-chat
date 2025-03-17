/**
 * Conversation management functions for UBC LLM Chat plugin.
 *
 * @package UBC\LLMChat
 */

(function(window, api, ui) {
    'use strict';

    /**
     * Conversation management functions for the UBC LLM Chat plugin.
     */
    const UBCLLMChatConversations = {
        /**
         * Load conversations from the API.
         *
         * @param {Object} instance - The chat instance.
         */
        loadConversations: function(instance) {
            // Show loading state
            instance.state.isLoading = true;

            // Create notification callback
            const notificationCallback = (message, type) => {
                ui.showNotification(message, type, instance.elements, instance.templates);
            };

            // Make API request
            api.apiRequest('/conversations', {}, notificationCallback)
                .then(data => {
                    // Store conversations
                    instance.state.conversations = data;

                    // Render conversations
                    ui.renderConversations(
                        instance.state.conversations,
                        instance.elements,
                        instance.state.currentConversationId,
                        instance.templates
                    );

                    // Hide loading state
                    instance.state.isLoading = false;

                    // If there are conversations, load the first one
                    if (data.length > 0) {
                        this.loadConversation(data[0].id, instance);
                    } else {
                        // Show welcome message
                        instance.elements.welcome.style.display = 'block';

                        // Hide conversation actions
                        instance.elements.exportButton.style.display = 'none';
                        instance.elements.renameButton.style.display = 'none';
                        instance.elements.deleteButton.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading conversations:', error);

                    // Hide loading state
                    instance.state.isLoading = false;

                    // Show empty state
                    instance.elements.conversationListEmpty.style.display = 'block';
                });
        },

        /**
         * Create a new conversation.
         *
         * @param {Object} instance - The chat instance.
         */
        createConversation: function(instance) {
            // Check if the user has reached the maximum number of conversations
            if (instance.state.conversations.length >= instance.maxConversations) {
                ui.showNotification(
                    window.ubc_llm_chat_public.i18n.conversation_limit,
                    'warning',
                    instance.elements,
                    instance.templates
                );
                return;
            }

            // Show loading state
            instance.state.isLoading = true;

            // Prepare request data
            const data = {
                llm_service: instance.llmService,
                llm_model: instance.llmModel,
                temperature: instance.temperature,
                system_prompt: instance.systemPrompt
            };

            // Add test_mode parameter only if it's true
            if (window.ubc_llm_chat_public.test_mode === 'true') {
                data.test_mode = 'true';
            }

            // Create notification callback
            const notificationCallback = (message, type) => {
                ui.showNotification(message, type, instance.elements, instance.templates);
            };

            // Make API request
            api.apiRequest('/conversations', {
                method: 'POST',
                body: data
            }, notificationCallback)
                .then(data => {
                    // Add the new conversation to the state
                    instance.state.conversations.unshift(data);

                    // Render conversations
                    ui.renderConversations(
                        instance.state.conversations,
                        instance.elements,
                        instance.state.currentConversationId,
                        instance.templates
                    );

                    // Load the new conversation
                    this.loadConversation(data.id, instance);

                    // Hide loading state
                    instance.state.isLoading = false;
                })
                .catch(error => {
                    console.error('Error creating conversation:', error);

                    // Hide loading state
                    instance.state.isLoading = false;
                });
        },

        /**
         * Load a specific conversation.
         *
         * @param {string} conversationId - The ID of the conversation to load.
         * @param {Object} instance - The chat instance.
         */
        loadConversation: function(conversationId, instance) {
            // Don't reload the current conversation
            if (instance.state.currentConversationId === conversationId) {
                return;
            }

            // Save the current input if there is one
            if (instance.state.currentConversationId && instance.elements.input.value.trim()) {
                instance.state.unsavedInputs.set(instance.state.currentConversationId, instance.elements.input.value);
            }

            // Show loading state
            instance.state.isLoading = true;

            // Create notification callback
            const notificationCallback = (message, type) => {
                ui.showNotification(message, type, instance.elements, instance.templates);
            };

            // Make API request
            api.apiRequest(`/conversations/${conversationId}`, {}, notificationCallback)
                .then(data => {
                    // Store the current conversation ID
                    instance.state.currentConversationId = conversationId;

                    // Render the conversation
                    ui.renderConversation(data, instance.elements, instance);

                    // Highlight the current conversation in the list
                    const conversationButtons = instance.elements.conversationListItems.querySelectorAll('.ubc-llm-chat-conversation-button');
                    conversationButtons.forEach(button => {
                        button.classList.remove('active');
                    });

                    const currentConversationItem = instance.elements.conversationListItems.querySelector(`.ubc-llm-chat-conversation-item[data-conversation-id="${conversationId}"]`);
                    if (currentConversationItem) {
                        const button = currentConversationItem.querySelector('.ubc-llm-chat-conversation-button');
                        if (button) {
                            button.classList.add('active');
                        }
                    }

                    // Restore any unsaved input
                    if (instance.state.unsavedInputs.has(conversationId)) {
                        instance.elements.input.value = instance.state.unsavedInputs.get(conversationId);

                        // Auto-resize the textarea
                        instance.elements.input.style.height = 'auto';
                        instance.elements.input.style.height = (instance.elements.input.scrollHeight) + 'px';
                    } else {
                        instance.elements.input.value = '';
                    }

                    // Show conversation actions
                    instance.elements.exportButton.style.display = 'flex';
                    instance.elements.renameButton.style.display = 'flex';
                    instance.elements.deleteButton.style.display = 'flex';

                    // Hide loading state
                    instance.state.isLoading = false;

                    // Focus the input
                    instance.elements.input.focus();

                    // Check if there's a pending message to send
                    if (instance.state.pendingMessage) {
                        const pendingMessage = instance.state.pendingMessage;
                        instance.state.pendingMessage = null; // Clear the pending message

                        // Use the messages module to send the pending message
                        if (window.UBCLLMChatMessages) {
                            window.UBCLLMChatMessages.sendMessage(pendingMessage, instance, () => this.createConversation(instance));
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading conversation:', error);

                    // Hide loading state
                    instance.state.isLoading = false;
                });
        },

        /**
         * Rename a conversation.
         *
         * @param {string} conversationId - The ID of the conversation to rename.
         * @param {string} newName - The new name for the conversation.
         * @param {Object} instance - The chat instance.
         */
        renameConversation: function(conversationId, newName, instance) {
            // Create notification callback
            const notificationCallback = (message, type) => {
                ui.showNotification(message, type, instance.elements, instance.templates);
            };

            // Make API request
            api.apiRequest(`/conversations/${conversationId}`, {
                method: 'PUT',
                body: {
                    title: newName
                }
            }, notificationCallback)
                .then(data => {
                    // Update the conversation in the state
                    const conversationIndex = instance.state.conversations.findIndex(c => c.id === conversationId);
                    if (conversationIndex !== -1) {
                        instance.state.conversations[conversationIndex] = data;
                    }

                    // Update the conversation list
                    ui.renderConversations(
                        instance.state.conversations,
                        instance.elements,
                        instance.state.currentConversationId,
                        instance.templates
                    );

                    // Show success notification
                    ui.showNotification(
                        window.ubc_llm_chat_public.i18n.renamed,
                        'success',
                        instance.elements,
                        instance.templates
                    );
                })
                .catch(error => {
                    console.error('Error renaming conversation:', error);
                });
        },

        /**
         * Delete a conversation.
         *
         * @param {string} conversationId - The ID of the conversation to delete.
         * @param {Object} instance - The chat instance.
         */
        deleteConversation: function(conversationId, instance) {
            // Create notification callback
            const notificationCallback = (message, type) => {
                ui.showNotification(message, type, instance.elements, instance.templates);
            };

            // Make API request
            api.apiRequest(`/conversations/${conversationId}`, {
                method: 'DELETE'
            }, notificationCallback)
                .then(data => {
                    // Remove the conversation from the state
                    instance.state.conversations = instance.state.conversations.filter(c => c.id !== conversationId);

                    // Clear the current conversation if it was deleted
                    if (instance.state.currentConversationId === conversationId) {
                        instance.state.currentConversationId = null;

                        // Clear messages
                        instance.elements.messages.innerHTML = '';

                        // Show welcome message
                        instance.elements.welcome.style.display = 'block';

                        // Hide conversation actions
                        instance.elements.exportButton.style.display = 'none';
                        instance.elements.renameButton.style.display = 'none';
                        instance.elements.deleteButton.style.display = 'none';

                        // Clear input
                        instance.elements.input.value = '';
                    }

                    // Update the conversation list
                    ui.renderConversations(
                        instance.state.conversations,
                        instance.elements,
                        instance.state.currentConversationId,
                        instance.templates
                    );

                    // If there are conversations, load the first one
                    if (instance.state.conversations.length > 0) {
                        this.loadConversation(instance.state.conversations[0].id, instance);
                    }

                    // Show success notification
                    ui.showNotification(
                        window.ubc_llm_chat_public.i18n.deleted,
                        'success',
                        instance.elements,
                        instance.templates
                    );
                })
                .catch(error => {
                    console.error('Error deleting conversation:', error);
                });
        },

        /**
         * Export a conversation as markdown.
         *
         * @param {string} conversationId - The ID of the conversation to export.
         * @param {Object} instance - The chat instance.
         */
        exportConversation: function(conversationId, instance) {
            // Create notification callback
            const notificationCallback = (message, type) => {
                ui.showNotification(message, type, instance.elements, instance.templates);
            };

            // Make API request
            api.apiRequest(`/conversations/${conversationId}/export`, {}, notificationCallback)
                .then(data => {
                    // Create a blob with the markdown content
                    const blob = new Blob([data.content], { type: 'text/markdown' });

                    // Create a URL for the blob
                    const url = URL.createObjectURL(blob);

                    // Create a temporary link element
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = data.filename;

                    // Append the link to the document
                    document.body.appendChild(link);

                    // Click the link to trigger the download
                    link.click();

                    // Remove the link from the document
                    document.body.removeChild(link);

                    // Revoke the URL to free up memory
                    URL.revokeObjectURL(url);

                    // Show success notification
                    ui.showNotification(
                        window.ubc_llm_chat_public.i18n.exported || 'Conversation exported successfully.',
                        'success',
                        instance.elements,
                        instance.templates
                    );
                })
                .catch(error => {
                    console.error('Error exporting conversation:', error);
                });
        }
    };

    // Expose the module
    window.UBCLLMChatConversations = UBCLLMChatConversations;
})(window, window.UBCLLMChatAPI, window.UBCLLMChatUI);