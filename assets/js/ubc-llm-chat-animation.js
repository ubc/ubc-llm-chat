/**
 * Animation controller for UBC LLM Chat plugin.
 *
 * Handles character-by-character animation for message streaming.
 *
 * @package UBC\LLMChat
 */

(function(window, utils) {
    'use strict';

    /**
     * Animation controller for the UBC LLM Chat plugin.
     */
    const UBCLLMChatAnimation = {
        /**
         * Animation configuration.
         */
        config: {
            // Animation speeds (milliseconds)
            characterSpeed: 20,  // Delay between characters
            fadeInSpeed: 200,    // Duration of fade-in animation
            // Formatting options
            formattingDebounce: 100, // Debounce time for formatting (ms)
            formattingEnabled: true  // Whether to format content during animation
        },

        /**
         * Animation state for each message.
         */
        messageStates: new Map(),

        /**
         * Initialize a new message animation.
         *
         * @param {string} messageId - Unique identifier for the message
         * @param {HTMLElement} messageElement - The message element to animate
         * @param {Function} onComplete - Callback when animation is complete
         * @return {Object} Animation state object
         */
        initMessageAnimation: function(messageId, messageElement, onComplete = null) {
            // Create animation state object
            const state = {
                messageId: messageId,
                messageElement: messageElement,
                contentElement: messageElement.querySelector('.ubc-llm-chat-message-content'),
                buffer: '',            // Raw buffer of all received content
                displayedContent: '',  // Content that has been displayed so far
                pendingContent: '',    // Content waiting to be animated
                isAnimating: false,    // Whether animation is currently running
                animationFrame: null,  // Current animation frame
                onComplete: onComplete, // Callback when animation is complete
                formattingOptions: {}, // Options for formatting
                lastFormattedContent: '', // Last formatted content
                lastFormattedLength: 0,   // Length of content when last formatted
                formattingThreshold: 10   // Minimum characters to add before reformatting
            };

            console.log('Initializing animation for message:', messageId, {
                hasExistingState: this.messageStates.has(messageId),
                hasOnComplete: !!onComplete,
                hasContentElement: !!state.contentElement
            });

            // Store the state
            this.messageStates.set(messageId, state);

            console.log('Animation state initialized:', {
                messageId: messageId,
                totalStates: this.messageStates.size
            });

            return state;
        },

        /**
         * Add content to the message buffer.
         *
         * @param {string} messageId - Unique identifier for the message
         * @param {string} content - Content to add to the buffer
         */
        addToBuffer: function(messageId, content) {
            const state = this.messageStates.get(messageId);

            if (!state) {
                console.error('Animation state not found for message:', messageId);
                return;
            }

            // Add content to buffer
            state.buffer += content;
            state.pendingContent += content;

            console.log('Added content to buffer:', {
                messageId: messageId,
                contentLength: content.length,
                totalBufferLength: state.buffer.length,
                pendingContentLength: state.pendingContent.length,
                isAnimating: state.isAnimating
            });

            // Start animation if not already running
            if (!state.isAnimating) {
                console.log('Starting animation for message:', messageId);
                this.animateMessage(messageId);
            } else {
                console.log('Animation already running, content will be animated as it becomes available');
            }
        },

        /**
         * Animate the message content.
         *
         * @param {string} messageId - Unique identifier for the message
         */
        animateMessage: function(messageId) {
            const state = this.messageStates.get(messageId);

            if (!state) {
                console.error('Animation state not found for message:', messageId);
                return;
            }

            console.log('Starting animation for message:', messageId, {
                pendingContentLength: state.pendingContent ? state.pendingContent.length : 0,
                isAnimating: state.isAnimating
            });

            // Set animating flag
            state.isAnimating = true;

            // Use character-by-character animation
            this.animateCharacterByCharacter(state);
        },

        /**
         * Format the content using the formatter manager or utils.
         *
         * @param {string} content - The content to format
         * @param {Object} options - Formatting options
         * @return {string} The formatted content
         */
        formatContent: function(content, options = {}) {
            // Use the formatter manager if available, otherwise fall back to utils
            if (this.config.formattingEnabled) {
                return utils.formatMessageContent(content, options);
            }

            // If formatting is disabled, just escape HTML and convert newlines
            if (window.UBCLLMChatFormatterUtils) {
                return window.UBCLLMChatFormatterUtils.basicFormat(content);
            }

            // Last resort fallback
            return utils.formatMessageContent(content, { disableMarkdown: true });
        },

        /**
         * Animate character by character.
         *
         * @param {Object} state - Animation state object
         */
        animateCharacterByCharacter: function(state) {
            if (!state.pendingContent) {
                console.log('No more pending content for character animation, marking as complete');
                state.isAnimating = false;

                // If we have a finalCallback and all content has been displayed, clean up
                if (state.finalCallback && state.buffer === state.displayedContent) {
                    console.log('Animation complete with finalCallback, cleaning up');
                    this.cleanupAnimation(state.messageId);
                } else if (state.onComplete && state.buffer === state.displayedContent) {
                    console.log('Animation complete with onComplete callback');
                    state.onComplete();
                }
                return;
            }

            // Get the next character
            const nextChar = state.pendingContent.charAt(0);
            state.pendingContent = state.pendingContent.substring(1);
            state.displayedContent += nextChar;

            // Determine if we should reformat the content
            const shouldReformat =
                // Always format on the first character
                state.displayedContent.length === 1 ||
                // Or if we've added enough characters since the last formatting
                (state.displayedContent.length - state.lastFormattedLength) >= state.formattingThreshold ||
                // Or if we've reached the end of the content
                state.pendingContent.length === 0;

            // Update the content
            if (shouldReformat) {
                // Format the content
                const formattedContent = this.formatContent(state.displayedContent, state.formattingOptions);

                // Update the content element
                state.contentElement.innerHTML = formattedContent;

                // Update the last formatted state
                state.lastFormattedContent = formattedContent;
                state.lastFormattedLength = state.displayedContent.length;
            }

            // Scroll to the bottom
            if (state.messageElement.parentElement) {
                state.messageElement.parentElement.scrollTop = state.messageElement.parentElement.scrollHeight;
            }

            // Schedule the next character
            state.animationFrame = setTimeout(() => {
                this.animateCharacterByCharacter(state);
            }, this.config.characterSpeed);
        },

        /**
         * Display all content immediately without animation.
         *
         * @param {string} messageId - Unique identifier for the message
         */
        displayAllContent: function(messageId) {
            const state = this.messageStates.get(messageId);

            if (!state) {
                console.error('Animation state not found for message:', messageId);
                return;
            }

            console.log('Displaying all content immediately for message:', messageId, {
                bufferLength: state.buffer.length,
                pendingContentLength: state.pendingContent ? state.pendingContent.length : 0,
                displayedContentLength: state.displayedContent.length,
                hasFinalCallback: !!state.finalCallback,
                hasOnComplete: !!state.onComplete
            });

            // Update displayed content with all buffer content
            state.displayedContent = state.buffer;
            state.pendingContent = '';

            // Format and update the content
            const formattedContent = this.formatContent(state.displayedContent, state.formattingOptions);
            state.contentElement.innerHTML = formattedContent;
            state.lastFormattedContent = formattedContent;
            state.lastFormattedLength = state.displayedContent.length;

            // Scroll to the bottom
            if (state.messageElement.parentElement) {
                state.messageElement.parentElement.scrollTop = state.messageElement.parentElement.scrollHeight;
            }

            // Complete animation
            state.isAnimating = false;

            // If we have a finalCallback, clean up and call it
            if (state.finalCallback && typeof state.finalCallback === 'function') {
                console.log('Calling finalCallback from displayAllContent');
                this.cleanupAnimation(messageId);
            } else if (state.onComplete) {
                console.log('Calling onComplete from displayAllContent');
                state.onComplete();
            }
        },

        /**
         * Set formatting options for a message.
         *
         * @param {string} messageId - Unique identifier for the message
         * @param {Object} options - Formatting options
         */
        setFormattingOptions: function(messageId, options) {
            const state = this.messageStates.get(messageId);

            if (!state) {
                console.error('Animation state not found for message:', messageId);
                return;
            }

            state.formattingOptions = { ...state.formattingOptions, ...options };
        },

        /**
         * Enable or disable formatting during animation.
         *
         * @param {boolean} enabled - Whether formatting should be enabled
         */
        setFormattingEnabled: function(enabled) {
            this.config.formattingEnabled = !!enabled;
        },

        /**
         * Stop the animation for a specific message.
         *
         * @param {string} messageId - Unique identifier for the message
         * @param {boolean} displayRemaining - Whether to display remaining content
         * @param {boolean} fromCleanup - Whether this call is from cleanupAnimation
         */
        stopAnimation: function(messageId, displayRemaining = false, fromCleanup = false) {
            const state = this.messageStates.get(messageId);

            if (!state) {
                console.error('Animation state not found for message:', messageId);
                return;
            }

            console.log('Stopping animation for message:', messageId, {
                displayRemaining: displayRemaining,
                fromCleanup: fromCleanup,
                pendingContentLength: state.pendingContent ? state.pendingContent.length : 0,
                isAnimating: state.isAnimating,
                hasContent: state.pendingContent && state.pendingContent.length > 0
            });

            // Clear any pending animation
            if (state.animationFrame) {
                clearTimeout(state.animationFrame);
                state.animationFrame = null;
            }

            // Set animation flag to false
            state.isAnimating = false;

            // If we should display remaining content and there is content to display
            if (displayRemaining && state.pendingContent && state.pendingContent.length > 0) {
                if (fromCleanup) {
                    console.log('Called from cleanup - continuing animation instead of displaying all at once');
                    // If called from cleanup, we want to continue the animation rather than display all at once
                    state.isAnimating = true;
                    this.animateMessage(messageId);
                    return;
                }

                console.log('Displaying all remaining content for message:', messageId);
                this.displayAllContent(messageId);
            }
        },

        /**
         * Check if animation is complete for a message.
         *
         * @param {string} messageId - Unique identifier for the message
         * @return {boolean} True if animation is complete, false otherwise
         */
        isAnimationComplete: function(messageId) {
            const state = this.messageStates.get(messageId);

            if (!state) {
                console.log('No animation state found for message:', messageId);
                return true; // If state doesn't exist, consider animation complete
            }

            // Animation is complete if there's no pending content and not currently animating
            const isComplete = !state.pendingContent && !state.isAnimating;
            console.log('Checking if animation is complete:', {
                messageId: messageId,
                hasPendingContent: !!state.pendingContent,
                pendingContentLength: state.pendingContent ? state.pendingContent.length : 0,
                isAnimating: state.isAnimating,
                isComplete: isComplete
            });

            return isComplete;
        },

        /**
         * Mark animation as complete, but continue animating any remaining content.
         * This is used when the LLM has finished generating content but we still
         * want to animate the remaining buffered content.
         *
         * @param {string} messageId - Unique identifier for the message
         * @param {Function} finalCallback - Callback to call when animation is truly complete
         */
        markAnimationComplete: function(messageId, finalCallback) {
            const state = this.messageStates.get(messageId);

            if (!state) {
                console.log('No animation state found for message:', messageId);
                if (finalCallback && typeof finalCallback === 'function') {
                    finalCallback();
                }
                return;
            }

            console.log('Marking animation as complete for message:', messageId);
            console.log('Current animation state:', {
                pendingContent: state.pendingContent ? state.pendingContent.length : 0,
                isAnimating: state.isAnimating
            });

            // Store the final callback
            state.finalCallback = finalCallback;

            // If animation is already complete, call the callback immediately
            if (this.isAnimationComplete(messageId)) {
                console.log('Animation is already complete, cleaning up now');
                this.cleanupAnimation(messageId);
            } else {
                console.log('Animation is not complete, will continue animating remaining content');
            }
        },

        /**
         * Clean up animation state for a message.
         *
         * @param {string} messageId - Unique identifier for the message
         */
        cleanupAnimation: function(messageId) {
            const state = this.messageStates.get(messageId);

            if (!state) {
                console.log('No animation state found for cleanup:', messageId);
                return;
            }

            console.log('Attempting to clean up animation for message:', messageId, {
                hasPendingContent: !!state.pendingContent,
                pendingContentLength: state.pendingContent ? state.pendingContent.length : 0,
                isAnimating: state.isAnimating,
                hasFinalCallback: !!state.finalCallback
            });

            // If there's still pending content, don't clean up yet
            if (state.pendingContent && state.isAnimating) {
                console.log('Not cleaning up yet, still have pending content to animate');
                return;
            }

            console.log('Proceeding with cleanup for message:', messageId);

            // Stop any ongoing animation
            this.stopAnimation(messageId, true, true);

            // Call the final callback if it exists
            if (state.finalCallback && typeof state.finalCallback === 'function') {
                console.log('Calling final callback during cleanup');
                state.finalCallback();
            } else {
                console.log('No final callback to call during cleanup');
            }

            // Remove the state
            this.messageStates.delete(messageId);
            console.log('Animation state removed for message:', messageId);
        }
    };

    // Expose the module
    window.UBCLLMChatAnimation = UBCLLMChatAnimation;
})(window, window.UBCLLMChatUtils);