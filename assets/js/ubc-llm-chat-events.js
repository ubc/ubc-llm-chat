/**
 * Event handling functions for UBC LLM Chat plugin.
 *
 * @package UBC\LLMChat
 */

(function(window, ui, conversations, messages) {
    'use strict';

    /**
     * Event handling functions for the UBC LLM Chat plugin.
     */
    const UBCLLMChatEvents = {
        /**
         * Bind event handlers to DOM elements.
         *
         * @param {Object} instance - The chat instance.
         */
        bindEvents: function(instance) {
            // Bind 'this' to the class instance for all event handlers
            const self = instance;

            // Toggle conversation list on mobile
            if (instance.elements.toggleConversationListButton) {
                instance.elements.toggleConversationListButton.addEventListener('click', function() {
                    console.log('Toggle button clicked');

                    // Get the current state
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';

                    // Toggle the state
                    this.setAttribute('aria-expanded', !isExpanded);

                    // Toggle the class on the conversation list items
                    if (self.elements.conversationListItems) {
                        if (!isExpanded) {
                            // Opening the list
                            self.elements.conversationListItems.classList.add('open');
                            console.log('Added open class');

                            // Force a reflow to ensure the transition works
                            self.elements.conversationListItems.offsetHeight;
                        } else {
                            // Closing the list
                            self.elements.conversationListItems.classList.remove('open');
                            console.log('Removed open class');
                        }
                    } else {
                        console.error('Conversation list items element not found');
                    }
                });
            } else {
                console.error('Toggle conversation list button not found');
            }

            // Create new conversation
            if (instance.elements.newConversationButton) {
                instance.elements.newConversationButton.addEventListener('click', function() {
                    conversations.createConversation(self);
                });
            }

            // Send message
            if (instance.elements.form) {
                instance.elements.form.addEventListener('submit', function(e) {
                    console.log('Form submitted');
                    e.preventDefault();

                    const message = self.elements.input.value.trim();
                    console.log('Input value:', message);

                    if (message) {
                        console.log('Message is not empty, proceeding with send');
                        // Clear the input - if there's an error, the message will be restored
                        self.elements.input.value = '';

                        // Auto-resize the textarea
                        self.elements.input.style.height = 'auto';

                        // Send the message
                        messages.sendMessage(message, self, () => conversations.createConversation(self));
                    } else {
                        console.log('Message is empty, showing notification');
                        ui.showNotification(
                            window.ubc_llm_chat_public.i18n.empty_message,
                            'warning',
                            self.elements,
                            self.templates
                        );
                    }
                });
            }

            // Add a direct click handler to the submit button as a backup
            if (instance.elements.submitButton) {
                instance.elements.submitButton.addEventListener('click', function(e) {
                    console.log('Submit button clicked directly');
                    e.preventDefault();

                    const message = self.elements.input.value.trim();
                    console.log('Input value from button click:', message);

                    if (message) {
                        console.log('Message is not empty, proceeding with send from button click');
                        // Clear the input - if there's an error, the message will be restored
                        self.elements.input.value = '';

                        // Auto-resize the textarea
                        self.elements.input.style.height = 'auto';

                        // Send the message
                        messages.sendMessage(message, self, () => conversations.createConversation(self));
                    } else {
                        console.log('Message is empty, showing notification from button click');
                        ui.showNotification(
                            window.ubc_llm_chat_public.i18n.empty_message,
                            'warning',
                            self.elements,
                            self.templates
                        );
                    }
                });
            }

            // Auto-resize textarea as user types
            if (instance.elements.input) {
                instance.elements.input.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });

                // Save unsaved input when user types
                instance.elements.input.addEventListener('input', function() {
                    if (self.state.currentConversationId) {
                        self.state.unsavedInputs.set(self.state.currentConversationId, this.value);
                    }
                });

                // Handle enter key and mod+enter
                instance.elements.input.addEventListener('keydown', function(e) {
                    // Check if Enter key was pressed
                    if (e.key === 'Enter') {
                        // If Ctrl, Alt, Meta (Command), or Shift key is pressed with Enter, insert a new line
                        if (e.ctrlKey || e.altKey || e.metaKey || e.shiftKey) {
                            // Do nothing, allow the default behavior (new line)
                            return;
                        } else {
                            // Otherwise, submit the form
                            e.preventDefault();

                            const message = self.elements.input.value.trim();

                            if (message) {
                                // Save the current input before clearing it
                                if (self.state.currentConversationId) {
                                    self.state.unsavedInputs.set(self.state.currentConversationId, message);
                                }

                                self.elements.input.value = '';
                                messages.sendMessage(message, self, () => conversations.createConversation(self));

                                // Auto-resize the textarea
                                self.elements.input.style.height = 'auto';
                            } else {
                                ui.showNotification(
                                    window.ubc_llm_chat_public.i18n.empty_message,
                                    'warning',
                                    self.elements,
                                    self.templates
                                );
                            }
                        }
                    }
                });
            }

            // Export conversation
            if (instance.elements.exportButton) {
                instance.elements.exportButton.addEventListener('click', function() {
                    if (self.state.currentConversationId) {
                        conversations.exportConversation(self.state.currentConversationId, self);
                    } else {
                        ui.showNotification(
                            window.ubc_llm_chat_public.i18n.no_conversation_selected,
                            'warning',
                            self.elements,
                            self.templates
                        );
                    }
                });
            }

            // Show rename modal
            if (instance.elements.renameButton) {
                instance.elements.renameButton.addEventListener('click', function() {
                    if (self.state.currentConversationId) {
                        // Find the current conversation
                        const conversation = self.state.conversations.find(c => c.id === self.state.currentConversationId);

                        if (conversation) {
                            // Make sure we have the rename modal
                            if (!self.elements.renameModal) {
                                self.elements.renameModal = document.getElementById(`ubc-llm-chat-rename-modal-${self.instanceId}`);
                                console.log('Looking for rename modal with ID:', `ubc-llm-chat-rename-modal-${self.instanceId}`);
                            }

                            // Check if the rename modal exists
                            if (!self.elements.renameModal) {
                                console.error('Rename modal not found');
                                ui.showNotification(
                                    'Rename functionality is not available',
                                    'error',
                                    self.elements,
                                    self.templates
                                );
                                return;
                            }

                            console.log('Found rename modal:', self.elements.renameModal);

                            // Find the rename input using multiple methods
                            if (!self.elements.renameInput) {
                                // Try by ID first
                                self.elements.renameInput = document.getElementById(`ubc-llm-chat-rename-input-${self.instanceId}`);
                                console.log('Looking for rename input with ID:', `ubc-llm-chat-rename-input-${self.instanceId}`);

                                // If not found, try by class within the modal
                                if (!self.elements.renameInput && self.elements.renameModal) {
                                    self.elements.renameInput = self.elements.renameModal.querySelector('.ubc-llm-chat-rename-input');
                                    console.log('Looking for rename input by class within modal');
                                }

                                // If still not found, try by class in the entire document
                                if (!self.elements.renameInput) {
                                    self.elements.renameInput = document.querySelector('.ubc-llm-chat-rename-input');
                                    console.log('Looking for rename input by class in document');
                                }

                                // Log all inputs in the document for debugging
                                console.log('All inputs in document:', document.querySelectorAll('input'));
                                console.log('All inputs in modal:', self.elements.renameModal.querySelectorAll('input'));
                            }

                            if (!self.elements.renameConfirmButton && self.elements.renameModal) {
                                self.elements.renameConfirmButton = self.elements.renameModal.querySelector('.ubc-llm-chat-rename-confirm-button');
                                console.log('Looking for rename confirm button');
                            }

                            // Check if the rename input exists
                            if (!self.elements.renameInput) {
                                console.error('Rename input not found');
                                ui.showNotification(
                                    'Rename functionality is not available',
                                    'error',
                                    self.elements,
                                    self.templates
                                );
                                return;
                            }

                            console.log('Found rename input:', self.elements.renameInput);

                            // Set the input value and show the modal
                            self.elements.renameInput.value = conversation.title;
                            ui.showModal(self.elements.renameModal, self.debug);
                            self.elements.renameInput.focus();
                        }
                    } else {
                        ui.showNotification(
                            window.ubc_llm_chat_public.i18n.no_conversation_selected,
                            'warning',
                            self.elements,
                            self.templates
                        );
                    }
                });
            }

            // Rename conversation
            if (instance.elements.renameModal) {
                // Find the rename confirm button if not already set
                if (!instance.elements.renameConfirmButton) {
                    instance.elements.renameConfirmButton = instance.elements.renameModal.querySelector('.ubc-llm-chat-rename-confirm-button');
                }

                // Find the rename form if not already set
                if (!instance.elements.renameForm) {
                    instance.elements.renameForm = instance.elements.renameModal.querySelector('.ubc-llm-chat-rename-form');
                }

                // Add event listener to the rename form to handle form submission
                if (instance.elements.renameForm) {
                    instance.elements.renameForm.addEventListener('submit', function(e) {
                        e.preventDefault();

                        // Find the rename input if not already set
                        if (!self.elements.renameInput) {
                            self.elements.renameInput = document.getElementById(`ubc-llm-chat-rename-input-${self.instanceId}`);
                        }

                        if (!self.elements.renameInput) {
                            console.error('Rename input not found');
                            ui.showNotification(
                                'Rename functionality is not available',
                                'error',
                                self.elements,
                                self.templates
                            );
                            return;
                        }

                        const newName = self.elements.renameInput.value.trim();

                        if (newName && self.state.currentConversationId) {
                            conversations.renameConversation(self.state.currentConversationId, newName, self);
                            if (self.elements.renameModal) {
                                ui.hideModal(self.elements.renameModal, self.debug);
                            }
                        } else {
                            ui.showNotification(
                                window.ubc_llm_chat_public.i18n.empty_name,
                                'warning',
                                self.elements,
                                self.templates
                            );
                        }
                    });
                }

                if (instance.elements.renameConfirmButton) {
                    instance.elements.renameConfirmButton.addEventListener('click', function() {
                        // Find the rename input if not already set
                        if (!self.elements.renameInput) {
                            self.elements.renameInput = document.getElementById(`ubc-llm-chat-rename-input-${self.instanceId}`);
                        }

                        if (!self.elements.renameInput) {
                            console.error('Rename input not found');
                            ui.showNotification(
                                'Rename functionality is not available',
                                'error',
                                self.elements,
                                self.templates
                            );
                            return;
                        }

                        const newName = self.elements.renameInput.value.trim();

                        if (newName && self.state.currentConversationId) {
                            conversations.renameConversation(self.state.currentConversationId, newName, self);
                            if (self.elements.renameModal) {
                                ui.hideModal(self.elements.renameModal, self.debug);
                            }
                        } else {
                            ui.showNotification(
                                window.ubc_llm_chat_public.i18n.empty_name,
                                'warning',
                                self.elements,
                                self.templates
                            );
                        }
                    });
                }
            }

            // Show delete modal
            if (instance.elements.deleteButton) {
                instance.elements.deleteButton.addEventListener('click', function() {
                    if (self.state.currentConversationId) {
                        ui.showModal(self.elements.deleteModal, self.debug);
                    } else {
                        ui.showNotification(
                            window.ubc_llm_chat_public.i18n.no_conversation_selected,
                            'warning',
                            self.elements,
                            self.templates
                        );
                    }
                });
            }

            // Delete conversation
            if (instance.elements.deleteConfirmButton) {
                instance.elements.deleteConfirmButton.addEventListener('click', function() {
                    if (self.state.currentConversationId) {
                        conversations.deleteConversation(self.state.currentConversationId, self);
                        ui.hideModal(self.elements.deleteModal, self.debug);
                    }
                });
            }

            // Close modals
            if (instance.elements.closeModalButtons) {
                instance.elements.closeModalButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const modal = this.closest('.ubc-llm-chat-modal');
                        if (modal) {
                            ui.hideModal(modal, self.debug);
                        }
                    });
                });
            }

            // Handle escape key for modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openModals = document.querySelectorAll('.ubc-llm-chat-modal[aria-hidden="false"]');
                    openModals.forEach(modal => {
                        ui.hideModal(modal, self.debug);
                    });
                }
            });

            // Delegate click events for conversation items
            if (instance.elements.conversationListItems) {
                instance.elements.conversationListItems.addEventListener('click', function(e) {
                    const conversationButton = e.target.closest('.ubc-llm-chat-conversation-button');
                    if (conversationButton) {
                        const conversationItem = conversationButton.closest('.ubc-llm-chat-conversation-item');
                        if (conversationItem) {
                            const conversationId = conversationItem.dataset.conversationId;
                            if (conversationId) {
                                conversations.loadConversation(conversationId, self);
                            }
                        }
                    }
                });
            }
        }
    };

    // Expose the module
    window.UBCLLMChatEvents = UBCLLMChatEvents;
})(window, window.UBCLLMChatUI, window.UBCLLMChatConversations, window.UBCLLMChatMessages);