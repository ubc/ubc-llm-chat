/**
 * LaTeX formatter for UBC LLM Chat plugin.
 *
 * This formatter uses the KaTeX library to parse and render LaTeX content.
 *
 * @package UBC\LLMChat
 */

(function(window) {
    'use strict';

    /**
     * LaTeX formatter class.
     */
    class UBCLLMChatLaTeXFormatter extends window.UBCLLMChatFormatterBase {
        /**
         * Constructor for the LaTeX formatter.
         */
        constructor() {
            // Call parent constructor with ID and priority (higher than Markdown)
            super('latex', 20);

            // Initialize KaTeX if available
            this.initKaTeX();
        }

        /**
         * Initialize the KaTeX library with custom options.
         */
        initKaTeX() {
            // Check if KaTeX is available
            if (!window.UBCLLMChatFormatterUtils.isLibraryLoaded('katex')) {
                console.error('KaTeX library not found. LaTeX formatting will not be available.');
                return;
            }

            this.katex = window.katex;

            // Configure KaTeX with safe options
            this.katexOptions = {
                throwOnError: false,
                errorColor: '#f44336',
                macros: {
                    // Define safe macros here if needed
                },
                trust: false,
                strict: false
            };

            console.log('KaTeX library initialized for LaTeX formatting');
        }

        /**
         * Determines if this formatter can handle the content.
         *
         * @param {string} content - The content to check.
         * @param {Object} options - Optional formatting options.
         * @return {boolean} True if this formatter can handle the content, false otherwise.
         */
        canFormat(content, options = {}) {
            // Check if KaTeX is available
            if (!this.katex) {
                return false;
            }

            // Check if LaTeX formatting is explicitly disabled
            if (options.disableLaTeX === true) {
                return false;
            }

            // Check if the content contains LaTeX syntax using the utility method
            return window.UBCLLMChatFormatterUtils.containsLaTeX(content);
        }

        /**
         * Format the content using LaTeX.
         *
         * @param {string} content - The content to format.
         * @param {Object} options - Optional formatting options.
         * @return {string} The formatted content.
         */
        format(content, options = {}) {
            // If KaTeX is not available, return the content unchanged
            if (!this.katex) {
                return content;
            }

            try {
                // Process inline LaTeX: $...$
                let formattedContent = this.processInlineLaTeX(content);

                // Process block LaTeX: $$...$$
                formattedContent = this.processBlockLaTeX(formattedContent);

                // Process LaTeX environments: \begin{...}...\end{...}
                formattedContent = this.processLaTeXEnvironments(formattedContent);

                return formattedContent;
            } catch (error) {
                console.error('LaTeX formatting failed:', error);
                // Fall back to returning the content unchanged
                return content;
            }
        }

        /**
         * Process inline LaTeX expressions ($...$).
         *
         * @param {string} content - The content to process.
         * @return {string} The processed content.
         */
        processInlineLaTeX(content) {
            // Debug log if enabled
            if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                console.log('Processing inline LaTeX');
            }

            // Replace $...$ with rendered LaTeX
            return content.replace(/\$([^\$\n]+)\$/g, (match, latex) => {
                try {
                    // Debug log if enabled
                    if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                        console.log('Rendering inline LaTeX:', latex);
                    }

                    return this.katex.renderToString(latex, {
                        ...this.katexOptions,
                        displayMode: false
                    });
                } catch (error) {
                    console.error('Error rendering inline LaTeX:', error, 'LaTeX:', latex);
                    return match;
                }
            });
        }

        /**
         * Process block LaTeX expressions ($$...$$).
         *
         * @param {string} content - The content to process.
         * @return {string} The processed content.
         */
        processBlockLaTeX(content) {
            // Debug log if enabled
            if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                console.log('Processing block LaTeX');
            }

            // Replace $$...$$ with rendered LaTeX
            return content.replace(/\$\$([\s\S]+?)\$\$/g, (match, latex) => {
                try {
                    // Debug log if enabled
                    if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                        console.log('Rendering block LaTeX:', latex);
                    }

                    return this.katex.renderToString(latex, {
                        ...this.katexOptions,
                        displayMode: true
                    });
                } catch (error) {
                    console.error('Error rendering block LaTeX:', error, 'LaTeX:', latex);
                    return match;
                }
            });
        }

        /**
         * Process LaTeX environments (\begin{...}...\end{...}).
         *
         * @param {string} content - The content to process.
         * @return {string} The processed content.
         */
        processLaTeXEnvironments(content) {
            // Debug log if enabled
            if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                console.log('Processing LaTeX environments');
            }

            // Replace \begin{...}...\end{...} with rendered LaTeX
            return content.replace(/\\begin\{([a-z]+)\}([\s\S]+?)\\end\{\1\}/gi, (match, env, latex) => {
                try {
                    // Debug log if enabled
                    if (window.ubc_llm_chat_public && window.ubc_llm_chat_public.debug) {
                        console.log('Rendering LaTeX environment:', env, 'Content:', latex);
                    }

                    const fullLatex = `\\begin{${env}}${latex}\\end{${env}}`;
                    return this.katex.renderToString(fullLatex, {
                        ...this.katexOptions,
                        displayMode: true
                    });
                } catch (error) {
                    console.error('Error rendering LaTeX environment:', error, 'Environment:', env, 'LaTeX:', latex);
                    return match;
                }
            });
        }
    }

    // Create an instance and register it with the formatter registry
    document.addEventListener('DOMContentLoaded', function() {
        if (window.UBCLLMChatFormatterRegistry) {
            const latexFormatter = new UBCLLMChatLaTeXFormatter();
            window.UBCLLMChatFormatterRegistry.register(latexFormatter);
        }
    });

    // Also expose the class for direct use
    window.UBCLLMChatLaTeXFormatter = UBCLLMChatLaTeXFormatter;
})(window);