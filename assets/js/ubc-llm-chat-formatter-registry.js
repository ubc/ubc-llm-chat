/**
 * Formatter registry for UBC LLM Chat plugin.
 *
 * This module provides a registry for formatters and allows add-ons
 * to register their own formatters.
 *
 * @package UBC\LLMChat
 */

(function(window) {
    'use strict';

    /**
     * Formatter registry class.
     */
    const UBCLLMChatFormatterRegistry = {
        /**
         * Initialize the registry.
         * This should be called when the page loads.
         */
        init: function() {
            // Create a custom event for formatter registration
            this.registrationEvent = new CustomEvent('ubc_llm_chat_formatter_register', {
                bubbles: true,
                cancelable: true,
                detail: {}
            });

            // Dispatch the event to allow add-ons to register formatters
            document.dispatchEvent(this.registrationEvent);

            console.log('Formatter registry initialized');
        },

        /**
         * Register a formatter.
         *
         * @param {UBCLLMChatFormatterBase} formatter - The formatter to register.
         * @return {boolean} True if registration was successful, false otherwise.
         */
        register: function(formatter) {
            // Check if formatter is valid
            if (!formatter || typeof formatter.format !== 'function' || typeof formatter.canFormat !== 'function') {
                console.error('Invalid formatter:', formatter);
                return false;
            }

            // Check if formatter manager exists
            if (!window.UBCLLMChatFormatterManager) {
                console.error('Formatter manager not found');
                return false;
            }

            // Register the formatter with the manager
            window.UBCLLMChatFormatterManager.register(formatter);
            return true;
        },

        /**
         * Register multiple formatters.
         *
         * @param {Array<UBCLLMChatFormatterBase>} formatters - Array of formatters to register.
         * @return {number} Number of successfully registered formatters.
         */
        registerBatch: function(formatters) {
            if (!Array.isArray(formatters)) {
                console.error('Expected an array of formatters');
                return 0;
            }

            let successCount = 0;
            for (const formatter of formatters) {
                if (this.register(formatter)) {
                    successCount++;
                }
            }

            return successCount;
        },

        /**
         * Remove a formatter by ID.
         *
         * @param {string} formatterId - The ID of the formatter to remove.
         * @return {boolean} True if formatter was removed, false otherwise.
         */
        remove: function(formatterId) {
            // Check if formatter manager exists
            if (!window.UBCLLMChatFormatterManager) {
                console.error('Formatter manager not found');
                return false;
            }

            return window.UBCLLMChatFormatterManager.remove(formatterId);
        }
    };

    // Expose the registry
    window.UBCLLMChatFormatterRegistry = UBCLLMChatFormatterRegistry;

    // Initialize the registry when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        UBCLLMChatFormatterRegistry.init();
    });
})(window);