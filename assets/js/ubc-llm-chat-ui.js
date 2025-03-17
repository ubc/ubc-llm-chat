/**
 * UI functions for UBC LLM Chat plugin.
 *
 * @package UBC\LLMChat
 */

(function(window, utils) {
    'use strict';

    /**
     * UI functions for the UBC LLM Chat plugin.
     */
    const UBCLLMChatUI = {
        /**
         * Render a conversation in the chat area.
         *
         * @param {Object} conversation - The conversation data.
         * @param {Object} elements - DOM elements.
         * @param {Object} instance - The chat instance.
         */
        renderConversation: function(conversation, elements, instance) {
            // Hide welcome message
            elements.welcome.style.display = 'none';

            // Clear messages
            elements.messages.innerHTML = '';

            // Check if there are messages
            if (!conversation.messages || conversation.messages.length === 0) {
                return;
            }

            // Render each message
            conversation.messages.forEach(message => {
                const messageHTML = this.renderMessage(message, conversation, instance.templates);
                elements.messages.insertAdjacentHTML('beforeend', messageHTML);
            });

            // Scroll to the bottom
            elements.chatArea.scrollTop = elements.chatArea.scrollHeight;
        },

        /**
         * Render all conversations in the conversation list.
         *
         * @param {Array} conversations - The conversations data.
         * @param {Object} elements - DOM elements.
         * @param {string} currentConversationId - The current conversation ID.
         * @param {Object} templates - Message templates.
         */
        renderConversations: function(conversations, elements, currentConversationId, templates) {
            // Clear conversation list
            elements.conversationListItems.innerHTML = '';

            // Check if there are conversations
            if (conversations.length === 0) {
                // Show empty state
                elements.conversationListEmpty.style.display = 'block';
                return;
            }

            // Hide empty state
            elements.conversationListEmpty.style.display = 'none';

            // Render each conversation
            conversations.forEach(conversation => {
                const conversationHTML = this.renderConversationItem(conversation, templates);
                elements.conversationListItems.insertAdjacentHTML('beforeend', conversationHTML);
            });

            // Highlight the current conversation
            if (currentConversationId) {
                const currentConversationItem = elements.conversationListItems.querySelector(`.ubc-llm-chat-conversation-item[data-conversation-id="${currentConversationId}"]`);
                if (currentConversationItem) {
                    const button = currentConversationItem.querySelector('.ubc-llm-chat-conversation-button');
                    if (button) {
                        button.classList.add('active');
                    }
                }
            }
        },

        /**
         * Render a conversation item in the conversation list.
         *
         * @param {Object} conversation - The conversation data.
         * @param {Object} templates - Message templates.
         * @return {string} The HTML for the conversation item.
         */
        renderConversationItem: function(conversation, templates) {
            // Use the template to create the HTML
            return templates.conversation_item
                .replace(/\{\{id\}\}/g, conversation.id)
                .replace(/\{\{title\}\}/g, conversation.title);
        },

        /**
         * Render a message in the chat area.
         *
         * @param {Object} message - The message data.
         * @param {Object} conversation - The conversation data (optional).
         * @param {Object} templates - Message templates.
         * @return {string} The HTML for the message.
         */
        renderMessage: function(message, conversation = null, templates) {
            // Format the timestamp
            const formattedTime = utils.formatTimestamp(message.timestamp);

            // Get the model name
            const modelName = conversation && conversation.llm_model ? conversation.llm_model : (window.ubc_llm_chat_public.i18n.assistant || 'Assistant');

            // Format the message content
            const formattedContent = utils.formatMessageContent(message.content);

            // Use the appropriate template based on the message role
            let template;
            if (message.role === 'user') {
                template = templates.user_message;
            } else {
                template = templates.assistant_message;
            }

            // Replace placeholders in the template
            return template
                .replace(/\{\{content\}\}/g, formattedContent)
                .replace(/\{\{time\}\}/g, formattedTime)
                .replace(/\{\{model\}\}/g, modelName);
        },

        /**
         * Show a notification.
         *
         * @param {string} message - The notification message.
         * @param {string} type - The notification type (success, error, warning, info).
         * @param {Object} elements - DOM elements.
         * @param {Object} templates - Message templates.
         */
        showNotification: function(message, type = 'info', elements, templates) {
            // Create notification element from template
            const template = templates.notification
                .replace('{{type}}', type)
                .replace('{{message}}', message);

            // Create a temporary div to hold the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = template.trim();

            // Get the notification element
            const notification = tempDiv.firstChild;

            // Add to notifications container
            elements.notifications.appendChild(notification);

            // Add close button event listener
            const closeButton = notification.querySelector('.ubc-llm-chat-notification-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    notification.remove();
                });
            }

            // Auto-remove after 5 seconds
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        },

        /**
         * Show a countdown for rate limiting.
         *
         * @param {number} seconds - The number of seconds to count down.
         * @param {Object} elements - DOM elements.
         * @param {Function} onComplete - Callback when countdown completes.
         * @return {number} The interval ID for the countdown.
         */
        showCountdown: function(seconds, elements, onComplete = null) {
            console.log('Showing countdown for', seconds, 'seconds');

            // Get the rate limit message element
            const rateLimitMessage = elements.rateLimitMessage;
            if (!rateLimitMessage) {
                console.error('Rate limit message element not found');
                return;
            }

            // Create the countdown message
            rateLimitMessage.innerHTML = `Rate limited. You can send another message in <span class="ubc-llm-chat-countdown-value">${seconds}</span> seconds.`;
            rateLimitMessage.style.display = 'block';

            // Get the countdown element
            const countdownElement = rateLimitMessage.querySelector('.ubc-llm-chat-countdown-value');

            // Disable the send button
            if (elements.submitButton) {
                console.log('Disabling submit button');
                elements.submitButton.disabled = true;
                elements.submitButton.setAttribute('aria-disabled', 'true');
            }

            // Start the countdown
            let remainingSeconds = seconds;
            const countdownInterval = setInterval(() => {
                remainingSeconds--;

                if (countdownElement) {
                    countdownElement.textContent = remainingSeconds;
                }

                if (remainingSeconds <= 0) {
                    // Clear the interval
                    clearInterval(countdownInterval);

                    // Clear the rate limit message
                    rateLimitMessage.innerHTML = '';
                    rateLimitMessage.style.display = 'none';

                    // Enable the send button
                    if (elements.submitButton) {
                        console.log('Enabling submit button');
                        elements.submitButton.disabled = false;
                        elements.submitButton.removeAttribute('aria-disabled');
                    }

                    // Call the completion callback
                    if (onComplete && typeof onComplete === 'function') {
                        onComplete();
                    }
                }
            }, 1000);

            return countdownInterval;
        },

        /**
         * Show a modal.
         *
         * @param {HTMLElement} modal - The modal element to show.
         * @param {boolean} debug - Whether debug mode is enabled.
         */
        showModal: function(modal, debug = false) {
            if (!modal) {
                console.error('Modal element not found');
                return;
            }

            if (debug) {
                console.log('Showing modal:', modal.id);
            }

            modal.setAttribute('aria-hidden', 'false');

            // Set focus on the first focusable element
            const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }

            // Trap focus within the modal
            this.trapFocus(modal);
        },

        /**
         * Hide a modal.
         *
         * @param {HTMLElement} modal - The modal element to hide.
         * @param {boolean} debug - Whether debug mode is enabled.
         */
        hideModal: function(modal, debug = false) {
            if (!modal) {
                console.error('Modal element not found');
                return;
            }

            if (debug) {
                console.log('Hiding modal:', modal.id);
            }

            modal.setAttribute('aria-hidden', 'true');

            // Restore focus to the element that opened the modal
            if (this.lastFocusedElement) {
                this.lastFocusedElement.focus();
            }
        },

        /**
         * Trap focus within an element.
         *
         * @param {HTMLElement} element - The element to trap focus within.
         */
        trapFocus: function(element) {
            // Store the element that had focus before opening the modal
            this.lastFocusedElement = document.activeElement;

            // Find all focusable elements
            const focusableElements = element.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const firstFocusableElement = focusableElements[0];
            const lastFocusableElement = focusableElements[focusableElements.length - 1];

            // Handle tab key to trap focus
            const handleTabKey = function(e) {
                // If not tab key, return
                if (e.key !== 'Tab') return;

                // Shift + Tab
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusableElement) {
                        e.preventDefault();
                        lastFocusableElement.focus();
                    }
                }
                // Tab
                else {
                    if (document.activeElement === lastFocusableElement) {
                        e.preventDefault();
                        firstFocusableElement.focus();
                    }
                }
            };

            // Add event listener
            element.addEventListener('keydown', handleTabKey);

            // Store the event listener so we can remove it later
            element._trapFocusHandler = handleTabKey;
        }
    };

    // Expose the module
    window.UBCLLMChatUI = UBCLLMChatUI;
})(window, window.UBCLLMChatUtils);