/**
 * Utility functions for UBC LLM Chat plugin.
 *
 * @package UBC\LLMChat
 */

(function(window) {
    'use strict';

    /**
     * Utility functions for the UBC LLM Chat plugin.
     */
    const UBCLLMChatUtils = {
        /**
         * Format a timestamp for display.
         *
         * @param {number} timestamp - The timestamp to format.
         * @return {string} The formatted timestamp.
         */
        formatTimestamp: function(timestamp) {
            const now = new Date();
            const date = new Date(timestamp * 1000);
            const diffInSeconds = Math.floor((now - date) / 1000);

            // Just now (less than a minute ago)
            if (diffInSeconds < 60) {
                return window.ubc_llm_chat_public.i18n.just_now;
            }

            // Minutes ago
            const diffInMinutes = Math.floor(diffInSeconds / 60);
            if (diffInMinutes < 60) {
                if (diffInMinutes === 1) {
                    return window.ubc_llm_chat_public.i18n.minute_ago;
                }
                return window.ubc_llm_chat_public.i18n.minutes_ago.replace('%d', diffInMinutes);
            }

            // Hours ago
            const diffInHours = Math.floor(diffInMinutes / 60);
            if (diffInHours < 24) {
                if (diffInHours === 1) {
                    return window.ubc_llm_chat_public.i18n.hour_ago;
                }
                return window.ubc_llm_chat_public.i18n.hours_ago.replace('%d', diffInHours);
            }

            // Yesterday
            const diffInDays = Math.floor(diffInHours / 24);
            if (diffInDays === 1) {
                return window.ubc_llm_chat_public.i18n.yesterday;
            }

            // Days ago (up to 7 days)
            if (diffInDays < 7) {
                return window.ubc_llm_chat_public.i18n.days_ago.replace('%d', diffInDays);
            }

            // Format as date
            return date.toLocaleDateString();
        },

        /**
         * Format message content for display.
         *
         * This function handles formatting of message content using the formatter manager.
         * If the formatter manager is not available, it falls back to basic formatting.
         * The content may already be filtered by the server-side ubc_llm_chat_message_content filter.
         *
         * @param {string} content - The message content to format.
         * @param {Object} options - Optional formatting options.
         * @returns {string} The formatted content.
         */
        formatMessageContent: function(content, options = {}) {
            // If content is empty or not a string, return empty string
            if (!content || typeof content !== 'string') {
                return '';
            }

            // Log original content for debugging (only in development)
            if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                console.log('formatMessageContent ORIGINAL', content);
            }

            // If the formatter manager is available, use it
            if (window.UBCLLMChatFormatterManager) {
                try {
                    const formattedContent = window.UBCLLMChatFormatterManager.format(content, options);

                    // Log formatted content for debugging (only in development)
                    if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                        console.log('formatMessageContent FORMATTED', formattedContent);
                    }

                    return formattedContent;
                } catch (error) {
                    console.error('Error formatting content:', error);
                    // Fall back to basic formatting
                }
            }

            // If formatter manager is not available or an error occurred, use basic formatting

            // If formatter utils are available, use them
            if (window.UBCLLMChatFormatterUtils) {
                return window.UBCLLMChatFormatterUtils.basicFormat(content);
            }

            // Last resort fallback - basic HTML escaping and newline conversion
            const escaped = content
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            // Log escaped content for debugging (only in development)
            if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                console.log('formatMessageContent ESCAPED (fallback)', escaped);
            }

            return escaped.replace(/\n/g, '<br>');
        }
    };

    // Expose the module
    window.UBCLLMChatUtils = UBCLLMChatUtils;
})(window);