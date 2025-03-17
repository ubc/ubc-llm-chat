/**
 * Formatter manager for UBC LLM Chat plugin.
 *
 * This class manages all formatters and handles their registration,
 * prioritization, and execution.
 *
 * @package UBC\LLMChat
 */

(function(window) {
    'use strict';

    /**
     * Formatter manager class.
     */
    class UBCLLMChatFormatterManager {
        /**
         * Constructor for the formatter manager.
         */
        constructor() {
            this.formatters = [];
            this.cache = new Map();
            this.cacheEnabled = true;
            this.cacheSize = 100; // Maximum number of cached items
        }

        /**
         * Register a formatter.
         *
         * @param {UBCLLMChatFormatterBase} formatter - The formatter to register.
         */
        register(formatter) {
            // Check if formatter is valid
            if (!formatter || typeof formatter.format !== 'function' || typeof formatter.canFormat !== 'function') {
                console.error('Invalid formatter:', formatter);
                return;
            }

            // Check if formatter with same ID already exists
            const existingIndex = this.formatters.findIndex(f => f.id === formatter.id);
            if (existingIndex !== -1) {
                // Replace existing formatter
                this.formatters[existingIndex] = formatter;
                console.log(`Replaced formatter with ID: ${formatter.id}`);
            } else {
                // Add new formatter
                this.formatters.push(formatter);
                console.log(`Registered formatter with ID: ${formatter.id}`);
            }

            // Sort formatters by priority (lower numbers run first)
            this.formatters.sort((a, b) => a.priority - b.priority);

            // Clear cache when formatters change
            this.clearCache();
        }

        /**
         * Remove a formatter by ID.
         *
         * @param {string} formatterId - The ID of the formatter to remove.
         * @return {boolean} True if formatter was removed, false otherwise.
         */
        remove(formatterId) {
            const initialLength = this.formatters.length;
            this.formatters = this.formatters.filter(formatter => formatter.id !== formatterId);

            const removed = initialLength !== this.formatters.length;
            if (removed) {
                console.log(`Removed formatter with ID: ${formatterId}`);
                // Clear cache when formatters change
                this.clearCache();
            }

            return removed;
        }

        /**
         * Format content using registered formatters.
         *
         * @param {string} content - The content to format.
         * @param {Object} options - Optional formatting options.
         * @return {string} The formatted content.
         */
        format(content, options = {}) {
            // If content is empty or not a string, return as is
            if (!content || typeof content !== 'string') {
                return content;
            }

            // Check cache if enabled
            if (this.cacheEnabled) {
                const cacheKey = this.getCacheKey(content, options);
                if (this.cache.has(cacheKey)) {
                    return this.cache.get(cacheKey);
                }
            }

            let formattedContent = content;

            // Apply each formatter in order
            for (const formatter of this.formatters) {
                if (formatter.canFormat(formattedContent, options)) {
                    try {
                        formattedContent = formatter.format(formattedContent, options);
                    } catch (error) {
                        console.error(`Formatter ${formatter.id} failed:`, error);
                    }
                }
            }

            // Cache the result if enabled
            if (this.cacheEnabled) {
                const cacheKey = this.getCacheKey(content, options);
                this.cache.set(cacheKey, formattedContent);

                // Trim cache if it gets too large
                if (this.cache.size > this.cacheSize) {
                    const oldestKey = this.cache.keys().next().value;
                    this.cache.delete(oldestKey);
                }
            }

            return formattedContent;
        }

        /**
         * Enable or disable caching.
         *
         * @param {boolean} enabled - Whether caching should be enabled.
         */
        setCacheEnabled(enabled) {
            this.cacheEnabled = !!enabled;
            if (!this.cacheEnabled) {
                this.clearCache();
            }
        }

        /**
         * Set the maximum cache size.
         *
         * @param {number} size - The maximum number of items to cache.
         */
        setCacheSize(size) {
            this.cacheSize = Math.max(1, parseInt(size, 10) || 100);

            // Trim cache if it's now too large
            while (this.cache.size > this.cacheSize) {
                const oldestKey = this.cache.keys().next().value;
                this.cache.delete(oldestKey);
            }
        }

        /**
         * Clear the cache.
         */
        clearCache() {
            this.cache.clear();
        }

        /**
         * Generate a cache key for content and options.
         *
         * @param {string} content - The content to format.
         * @param {Object} options - The formatting options.
         * @return {string} The cache key.
         */
        getCacheKey(content, options) {
            // Simple implementation - can be improved for performance
            return content + '::' + JSON.stringify(options);
        }
    }

    // Create a singleton instance
    const formatterManager = new UBCLLMChatFormatterManager();

    // Expose the manager
    window.UBCLLMChatFormatterManager = formatterManager;
})(window);