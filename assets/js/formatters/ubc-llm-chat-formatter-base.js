/**
 * Base formatter interface for UBC LLM Chat plugin.
 *
 * This serves as the base class/interface for all formatters.
 * Each formatter should extend this class and implement its methods.
 *
 * @package UBC\LLMChat
 */

(function(window) {
    'use strict';

    /**
     * Base formatter class that all formatters should extend.
     */
    class UBCLLMChatFormatterBase {
        /**
         * Constructor for the base formatter.
         *
         * @param {string} id - Unique identifier for the formatter.
         * @param {number} priority - Priority of the formatter (lower numbers run first).
         */
        constructor(id, priority = 10) {
            this.id = id;
            this.priority = priority;
        }

        /**
         * Determines if this formatter can handle the content.
         *
         * @param {string} content - The content to check.
         * @param {Object} options - Optional formatting options.
         * @return {boolean} True if this formatter can handle the content, false otherwise.
         */
        canFormat(content, options = {}) {
            // Default implementation always returns true
            return true;
        }

        /**
         * Formats the content.
         *
         * @param {string} content - The content to format.
         * @param {Object} options - Optional formatting options.
         * @return {string} The formatted content.
         */
        format(content, options = {}) {
            // Default implementation returns content unchanged
            return content;
        }
    }

    // Expose the class
    window.UBCLLMChatFormatterBase = UBCLLMChatFormatterBase;
})(window);