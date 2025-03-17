/**
 * Core functionality for UBC LLM Chat plugin.
 *
 * @package UBC\LLMChat
 */

(function(window, events, conversations) {
    'use strict';

    /**
     * Chat Interface Class
     *
     * Handles all the functionality for a single chat interface instance.
     */
    class UBCLLMChat {
        /**
         * Initialize the chat interface.
         *
         * @param {HTMLElement} container - The chat container element.
         */
        constructor(container) {
            // Store the container element
            this.container = container;

            // Get the instance ID and settings from data attributes
            this.instanceId = container.dataset.instanceId;
            this.llmService = container.dataset.llmService;
            this.llmModel = container.dataset.llmModel;
            this.maxMessages = parseInt(container.dataset.maxMessages, 10) || 20;
            this.maxConversations = parseInt(container.dataset.maxConversations, 10) || 10;
            this.temperature = parseFloat(container.dataset.temperature) || 0.7;
            this.systemPrompt = container.dataset.systemPrompt || '';

            // Check if debug mode is enabled
            this.debug = window.ubc_llm_chat_public.debug || false;

            if (this.debug) {
                console.log('UBC LLM Chat initialized with settings:', {
                    instanceId: this.instanceId,
                    llmService: this.llmService,
                    llmModel: this.llmModel,
                    maxMessages: this.maxMessages,
                    maxConversations: this.maxConversations,
                    temperature: this.temperature,
                    systemPrompt: this.systemPrompt
                });

                console.log('Message templates:', window.ubc_llm_chat_public.message_templates);
            }

            // Store DOM elements
            this.elements = {
                wrapper: container.querySelector('.ubc-llm-chat-wrapper'),
                conversationList: container.querySelector('.ubc-llm-chat-conversation-list'),
                conversationListItems: container.querySelector('.ubc-llm-chat-conversation-list-items'),
                conversationListEmpty: container.querySelector('.ubc-llm-chat-conversation-list-empty'),
                toggleConversationListButton: container.querySelector('.ubc-llm-chat-toggle-conversation-list-button'),
                newConversationButton: container.querySelector('.ubc-llm-chat-new-conversation-button'),
                chatArea: container.querySelector('.ubc-llm-chat-area'),
                messages: container.querySelector('.ubc-llm-chat-messages'),
                welcome: container.querySelector('.ubc-llm-chat-welcome'),
                form: container.querySelector('.ubc-llm-chat-form'),
                input: container.querySelector('.ubc-llm-chat-input'),
                submitButton: container.querySelector('.ubc-llm-chat-submit-button'),
                rateLimitMessage: container.querySelector('.ubc-llm-chat-rate-limit-message'),
                exportButton: container.querySelector('.ubc-llm-chat-export-button'),
                renameButton: container.querySelector('.ubc-llm-chat-rename-button'),
                deleteButton: container.querySelector('.ubc-llm-chat-delete-button'),
                deleteModal: document.getElementById(`ubc-llm-chat-delete-modal-${this.instanceId}`),
                deleteConfirmButton: container.querySelector('.ubc-llm-chat-delete-confirm-button'),
                renameModal: document.getElementById(`ubc-llm-chat-rename-modal-${this.instanceId}`),
                renameInput: null, // We'll set this later when needed
                renameConfirmButton: null, // We'll set this later when needed
                notifications: container.querySelector('.ubc-llm-chat-notifications'),
                closeModalButtons: container.querySelectorAll('[data-close-modal]')
            };

            // Check if all elements were found
            if (this.debug) {
                console.log('DOM Elements:', this.elements);

                // Check for missing elements
                const missingElements = [];
                for (const [key, element] of Object.entries(this.elements)) {
                    if (!element && key !== 'closeModalButtons' && key !== 'renameInput' && key !== 'renameConfirmButton') {
                        missingElements.push(key);
                    } else if (key === 'closeModalButtons' && (!element || element.length === 0)) {
                        missingElements.push(key);
                    }
                }

                if (missingElements.length > 0) {
                    console.warn('Missing DOM elements:', missingElements);
                }
            }

            // State variables
            this.state = {
                conversations: [],
                currentConversationId: null,
                isLoading: false,
                unsavedInputs: new Map(), // Store unsaved inputs when switching conversations
                eventSource: null, // For SSE streaming
                pendingMessage: null // For messages that need to be sent after a conversation is created
            };

            // Templates
            this.templates = window.ubc_llm_chat_public.message_templates;

            // Check if templates are loaded
            if (this.debug) {
                if (!this.templates) {
                    console.error('Message templates not found in window.ubc_llm_chat_public');
                } else {
                    const requiredTemplates = ['conversation_item', 'user_message', 'assistant_message', 'loading_message', 'notification'];
                    const missingTemplates = requiredTemplates.filter(template => !this.templates[template]);

                    if (missingTemplates.length > 0) {
                        console.error('Missing required templates:', missingTemplates);
                    }
                }
            }

            // Initialize the chat interface
            this.init();
        }

        /**
         * Initialize the chat interface.
         */
        init() {
            // Bind event handlers
            events.bindEvents(this);

            // Load conversations
            conversations.loadConversations(this);
        }
    }

    /**
     * Initialize all chat interfaces on the page.
     */
    function initChatInterfaces() {
        // Find all chat containers
        const chatContainers = document.querySelectorAll('.ubc-llm-chat-container');

        // Check if debug mode is enabled
        const debug = window.ubc_llm_chat_public.debug || false;

        if (debug) {
            console.log('Initializing chat interfaces, found containers:', chatContainers.length);

            // Check for rename modals in the DOM
            const renameModals = document.querySelectorAll('[id^="ubc-llm-chat-rename-modal-"]');
            console.log('Rename modals found in DOM:', renameModals.length);
            renameModals.forEach(modal => {
                console.log('Rename modal ID:', modal.id);
                const inputs = modal.querySelectorAll('input');
                console.log('Inputs in modal:', inputs.length);
                inputs.forEach(input => {
                    console.log('Input ID:', input.id, 'Class:', input.className);
                });
            });
        }

        // Initialize each chat container
        chatContainers.forEach(container => {
            new UBCLLMChat(container);
        });
    }

    // Initialize when the DOM is ready
    document.addEventListener('DOMContentLoaded', initChatInterfaces);

    // Expose the class
    window.UBCLLMChat = UBCLLMChat;
})(window, window.UBCLLMChatEvents, window.UBCLLMChatConversations);