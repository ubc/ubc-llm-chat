/**
 * Markdown formatter for UBC LLM Chat plugin.
 *
 * This formatter uses the Marked.js library to parse Markdown content.
 *
 * @package UBC\LLMChat
 */

(function(window) {
    'use strict';

    /**
     * Markdown formatter class.
     */
    class UBCLLMChatMarkdownFormatter extends window.UBCLLMChatFormatterBase {
        /**
         * Constructor for the Markdown formatter.
         */
        constructor() {
            // Call parent constructor with ID and priority
            super('markdown', 10);

            // Initialize Marked if available
            this.initMarked();
        }

        /**
         * Initialize the Marked library with custom options.
         */
        initMarked() {
            // Check if Marked is available
            if (!window.UBCLLMChatFormatterUtils.isLibraryLoaded('marked')) {
                console.error('Marked library not found. Markdown formatting will not be available.');
                return;
            }

            this.marked = window.marked;

            // Configure Marked with safe options
            this.marked.use({
                gfm: true,           // GitHub Flavored Markdown
                breaks: true,        // Convert line breaks to <br>
                headerIds: false,    // Don't add IDs to headers (for security)
                mangle: false,       // Don't mangle email addresses
                pedantic: false,     // Don't be pedantic about Markdown spec
                silent: true,        // Don't throw errors
                smartLists: true,    // Use smarter list behavior
                smartypants: true,   // Use "smart" typographic punctuation
                xhtml: false         // Don't use XHTML-style self-closing tags
            });

            // Set up a custom renderer to handle security concerns
            const renderer = new this.marked.Renderer();

            // Make links open in a new tab and add security attributes
            renderer.link = (href, title, text) => {
                // Debug the parameters
                if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                    console.log('Markdown link renderer:', { href, title, text });
                }

                // Extract URL, title, and text from the href object if needed
                let url = href;
                let linkText = text;
                let linkTitle = title;

                if (href && typeof href === 'object') {
                    // If href is an object, extract all needed properties
                    if (href.href) {
                        url = href.href;
                    } else if (href.toString && typeof href.toString === 'function') {
                        url = href.toString();
                    } else {
                        // If we can't get a usable URL, just use empty text
                        return '';
                    }

                    // If text is undefined but href.text exists, use that
                    if ((!linkText || linkText === 'undefined') && href.text) {
                        linkText = href.text;
                    }

                    // If title is undefined but href.title exists, use that
                    if ((!linkTitle || linkTitle === 'undefined') && href.title) {
                        linkTitle = href.title;
                    }
                }

                // Check if the link is safe
                if (!this.isSafeUrl(url)) {
                    console.warn('Link is not safe:', url);
                    return linkText || '';
                }

                // Ensure title and text are strings
                const titleStr = linkTitle ? String(linkTitle) : '';
                const textStr = linkText ? String(linkText) : url;
                const titleAttr = titleStr ? ` title="${titleStr}"` : '';

                return `<a href="${url}" target="_blank" rel="noopener noreferrer"${titleAttr}>${textStr}</a>`;
            };

            // Sanitize image sources
            renderer.image = (href, title, text) => {
                // Debug the parameters
                if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                    console.log('Markdown image renderer:', { href, title, text });
                }

                // Extract URL, title, and alt text from the href object if needed
                let url = href;
                let imgText = text;
                let imgTitle = title;

                if (href && typeof href === 'object') {
                    // If href is an object, extract all needed properties
                    if (href.href) {
                        url = href.href;
                    } else if (href.toString && typeof href.toString === 'function') {
                        url = href.toString();
                    } else {
                        // If we can't get a usable URL, just use empty text
                        return '';
                    }

                    // If text is undefined but href.text exists, use that
                    if ((!imgText || imgText === 'undefined') && href.text) {
                        imgText = href.text;
                    }

                    // If title is undefined but href.title exists, use that
                    if ((!imgTitle || imgTitle === 'undefined') && href.title) {
                        imgTitle = href.title;
                    }
                }

                // Check if the image source is safe
                if (!this.isSafeUrl(url)) {
                    console.warn('Image source is not safe:', url);
                    return imgText || '';
                }

                // Ensure title and alt text are strings
                const titleStr = imgTitle ? String(imgTitle) : '';
                const altText = imgText ? String(imgText) : '';
                const titleAttr = titleStr ? ` title="${titleStr}"` : '';

                return `<img src="${url}" alt="${altText}"${titleAttr}>`;
            };

            // Use the custom renderer
            this.marked.use({ renderer });

            console.log('Marked library initialized for Markdown formatting');
        }

        /**
         * Check if a URL is safe.
         *
         * @param {string|object} url - The URL to check.
         * @return {boolean} True if the URL is safe, false otherwise.
         */
        isSafeUrl(url) {
            // Check if URL is null, undefined, or not a string
            if (!url) {
                return false;
            }

            try {
                // Convert to string if it's not already
                const urlStr = String(url);

                // Debug the URL string
                if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                    console.log('Checking URL safety for:', urlStr);
                }

                // Check for javascript: URLs
                if (urlStr.toLowerCase().startsWith('javascript:')) {
                    return false;
                }

                // Check for data: URLs
                if (urlStr.toLowerCase().startsWith('data:')) {
                    // Only allow data: URLs for images with safe MIME types
                    const safeDataUrlPattern = /^data:image\/(png|gif|jpeg|jpg|webp|svg\+xml);base64,/i;
                    if (!safeDataUrlPattern.test(urlStr)) {
                        return false;
                    }
                }

                return true;
            } catch (e) {
                console.error('Error checking URL safety:', e);
                return false;
            }
        }

        /**
         * Determines if this formatter can handle the content.
         *
         * @param {string} content - The content to check.
         * @param {Object} options - Optional formatting options.
         * @return {boolean} True if this formatter can handle the content, false otherwise.
         */
        canFormat(content, options = {}) {
            // Check if Marked is available
            if (!this.marked) {
                return false;
            }

            // Check if Markdown formatting is explicitly disabled
            if (options.disableMarkdown === true) {
                return false;
            }

            // Check if the content contains Markdown syntax
            return window.UBCLLMChatFormatterUtils.containsMarkdown(content);
        }

        /**
         * Format the content using Markdown.
         *
         * @param {string} content - The content to format.
         * @param {Object} options - Optional formatting options.
         * @return {string} The formatted content.
         */
        format(content, options = {}) {
            // If Marked is not available, return the content with basic formatting
            if (!this.marked) {
                return window.UBCLLMChatFormatterUtils.basicFormat(content);
            }

            try {
                // Parse the Markdown content
                const formattedContent = this.marked.parse(content);
                return formattedContent;
            } catch (error) {
                console.error('Markdown formatting failed:', error);
                // Fall back to basic formatting
                return window.UBCLLMChatFormatterUtils.basicFormat(content);
            }
        }
    }

    // Create an instance and register it with the formatter registry
    document.addEventListener('DOMContentLoaded', function() {
        if (window.UBCLLMChatFormatterRegistry) {
            const markdownFormatter = new UBCLLMChatMarkdownFormatter();
            window.UBCLLMChatFormatterRegistry.register(markdownFormatter);
        }
    });

    // Also expose the class for direct use
    window.UBCLLMChatMarkdownFormatter = UBCLLMChatMarkdownFormatter;
})(window);