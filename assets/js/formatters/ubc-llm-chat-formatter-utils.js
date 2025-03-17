/**
 * Formatter utilities for UBC LLM Chat plugin.
 *
 * This module provides utility functions for formatters.
 *
 * @package UBC\LLMChat
 */

(function(window) {
    'use strict';

    /**
     * Formatter utilities.
     */
    const UBCLLMChatFormatterUtils = {
        /**
         * Escape HTML special characters.
         *
         * @param {string} text - The text to escape.
         * @return {string} The escaped text.
         */
        escapeHtml: function(text) {
            if (!text || typeof text !== 'string') {
                return '';
            }

            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        /**
         * Convert newlines to <br> tags.
         *
         * @param {string} text - The text to convert.
         * @return {string} The converted text.
         */
        nl2br: function(text) {
            if (!text || typeof text !== 'string') {
                return '';
            }

            return text.replace(/\n/g, '<br>');
        },

        /**
         * Basic text formatting (escape HTML and convert newlines to <br>).
         * This is the fallback formatting when no other formatters are available.
         *
         * @param {string} text - The text to format.
         * @return {string} The formatted text.
         */
        basicFormat: function(text) {
            return this.nl2br(this.escapeHtml(text));
        },

        /**
         * Check if a string contains Markdown syntax.
         *
         * @param {string} text - The text to check.
         * @return {boolean} True if the text contains Markdown syntax, false otherwise.
         */
        containsMarkdown: function(text) {
            if (!text || typeof text !== 'string') {
                return false;
            }

            // Check for common Markdown patterns
            const markdownPatterns = [
                /[*_]{1,2}[^*_]+[*_]{1,2}/,  // Bold/italic
                /^#+\s+/m,                   // Headers
                /^\s*[-*+]\s+/m,             // Unordered lists
                /^\s*\d+\.\s+/m,             // Ordered lists
                /\[.+?\]\(.+?\)/,            // Links
                /!\[.+?\]\(.+?\)/,           // Images
                /^>\s+/m,                    // Blockquotes
                /`{1,3}[^`]+`{1,3}/,         // Code (inline or block)
                /^\s*```/m,                  // Code blocks
                /^\s*---+\s*$/m,             // Horizontal rules
                /\|\s*[-:]+\s*\|/            // Tables
            ];

            return markdownPatterns.some(pattern => pattern.test(text));
        },

        /**
         * Check if a string contains LaTeX syntax.
         *
         * @param {string} text - The text to check.
         * @return {boolean} True if the text contains LaTeX syntax, false otherwise.
         */
        containsLaTeX: function(text) {
            if (!text || typeof text !== 'string') {
                return false;
            }

            // Check for inline LaTeX: $...$
            const inlineRegex = /\$[^\$\n]+\$/;

            // Check for block LaTeX: $$...$$
            const blockRegex = /\$\$[\s\S]+?\$\$/;

            // Check for LaTeX environments: \begin{...}...\end{...}
            const envRegex = /\\begin\{[a-z]+\}[\s\S]+?\\end\{[a-z]+\}/i;

            return (
                inlineRegex.test(text) ||
                blockRegex.test(text) ||
                envRegex.test(text)
            );
        },

        /**
         * Check if a library is loaded.
         *
         * @param {string} libraryName - The name of the library to check.
         * @return {boolean} True if the library is loaded, false otherwise.
         */
        isLibraryLoaded: function(libraryName) {
            return typeof window[libraryName] !== 'undefined';
        },

        /**
         * Create a debounced function.
         *
         * @param {Function} func - The function to debounce.
         * @param {number} wait - The debounce wait time in milliseconds.
         * @return {Function} The debounced function.
         */
        debounce: function(func, wait) {
            let timeout;

            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };

                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Expose the utilities
    window.UBCLLMChatFormatterUtils = UBCLLMChatFormatterUtils;
})(window);