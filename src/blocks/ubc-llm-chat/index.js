/**
 * UBC LLM Chat Block
 *
 * Registers a block for the UBC LLM Chat plugin.
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    RangeControl,
    ToggleControl,
    TextareaControl,
    Spinner
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Import styles
import './style.scss';
import './editor.scss';

/**
 * Register the block
 */
registerBlockType('ubc-llm-chat/chat', {
    apiVersion: 2,
    title: __('UBC LLM Chat', 'ubc-llm-chat'),
    description: __('Add an LLM chat interface to your page.', 'ubc-llm-chat'),
    category: 'widgets',
    icon: 'format-chat',
    keywords: ['chat', 'ai', 'llm', 'chatgpt', 'ollama'],
    supports: {
        html: false,
        // Enable the core Advanced panel
        customClassName: true,
    },

    // Define attributes - these match the ones in PHP
    attributes: {
        llmservice: {
            type: 'string',
            default: '',
        },
        llm: {
            type: 'string',
            default: '',
        },
        minimum_user_role: {
            type: 'string',
            default: '',
        },
        maxmessages: {
            type: 'number',
            default: 20,
        },
        maxconversations: {
            type: 'number',
            default: 10,
        },
        systemprompt: {
            type: 'string',
            default: '',
        },
        temperature: {
            type: 'number',
            default: 0.7,
        },
        debug_mode: {
            type: 'boolean',
            default: false,
        },
    },

    /**
     * Edit function
     */
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps({
            className: 'ubc-llm-chat-block-editor',
        });

        // State for settings data
        const [settings, setSettings] = useState(null);
        const [isLoading, setIsLoading] = useState(true);
        const [error, setError] = useState(null);

        // Fetch settings from REST API
        useEffect(() => {
            setIsLoading(true);
            apiFetch({ path: '/ubc-llm-chat/v1/settings' })
                .then((response) => {
                    setSettings(response);
                    setIsLoading(false);
                })
                .catch((err) => {
                    console.error('Error fetching UBC LLM Chat settings:', err);
                    setError(__('Could not load settings. Please check the console for errors.', 'ubc-llm-chat'));
                    setIsLoading(false);
                });
        }, []);

        // Prepare LLM service options
        const getLlmServiceOptions = () => {
            if (!settings) {
                return [{ label: __('Loading...', 'ubc-llm-chat'), value: '' }];
            }

            const options = [{ label: __('Default (from settings)', 'ubc-llm-chat'), value: '' }];

            if (settings.services) {
                if (settings.services.openai && settings.services.openai.enabled) {
                    options.push({ label: __('OpenAI', 'ubc-llm-chat'), value: 'openai' });
                }

                if (settings.services.ollama && settings.services.ollama.enabled) {
                    options.push({ label: __('Ollama', 'ubc-llm-chat'), value: 'ollama' });
                }
            }

            return options;
        };

        // Prepare LLM model options based on selected service
        const getLlmModelOptions = () => {
            if (!settings || !attributes.llmservice) {
                return [{ label: __('Default (from settings)', 'ubc-llm-chat'), value: '' }];
            }

            const options = [{ label: __('Default (from settings)', 'ubc-llm-chat'), value: '' }];

            if (attributes.llmservice === 'openai' && settings.services?.openai?.models) {
                const models = settings.services.openai.models;
                if (Array.isArray(models) && models.length > 0) {
                    models.forEach(model => {
                        options.push({ label: model, value: model });
                    });
                } else {
                    options.push({ label: __('No models available', 'ubc-llm-chat'), value: '', disabled: true });
                }
            } else if (attributes.llmservice === 'ollama' && settings.services?.ollama?.models) {
                const models = settings.services.ollama.models;
                if (Array.isArray(models) && models.length > 0) {
                    models.forEach(model => {
                        options.push({ label: model, value: model });
                    });
                } else {
                    options.push({ label: __('No models available', 'ubc-llm-chat'), value: '', disabled: true });
                }
            }

            return options;
        };

        // Get available user roles
        const userRoles = [
            { label: __('Default (from settings)', 'ubc-llm-chat'), value: '' },
            { label: __('Subscriber', 'ubc-llm-chat'), value: 'subscriber' },
            { label: __('Contributor', 'ubc-llm-chat'), value: 'contributor' },
            { label: __('Author', 'ubc-llm-chat'), value: 'author' },
            { label: __('Editor', 'ubc-llm-chat'), value: 'editor' },
            { label: __('Administrator', 'ubc-llm-chat'), value: 'administrator' },
        ];

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('LLM Settings', 'ubc-llm-chat')} initialOpen={true}>
                        {isLoading ? (
                            <div className="ubc-llm-chat-loading">
                                <Spinner />
                                <p>{__('Loading settings...', 'ubc-llm-chat')}</p>
                            </div>
                        ) : error ? (
                            <div className="ubc-llm-chat-error">
                                <p>{error}</p>
                            </div>
                        ) : (
                            <>
                                <SelectControl
                                    label={__('LLM Service', 'ubc-llm-chat')}
                                    value={attributes.llmservice}
                                    options={getLlmServiceOptions()}
                                    onChange={(value) => {
                                        // Reset the model when changing service
                                        setAttributes({
                                            llmservice: value,
                                            llm: ''
                                        });
                                    }}
                                    help={__('Select the LLM service to use. Only enabled services are shown.', 'ubc-llm-chat')}
                                />

                                <SelectControl
                                    label={__('LLM Model', 'ubc-llm-chat')}
                                    value={attributes.llm}
                                    options={getLlmModelOptions()}
                                    onChange={(value) => setAttributes({ llm: value })}
                                    help={__('Select the LLM model to use. Only available models for the selected service are shown.', 'ubc-llm-chat')}
                                    disabled={!attributes.llmservice}
                                />

                                <RangeControl
                                    label={__('Temperature', 'ubc-llm-chat')}
                                    value={attributes.temperature}
                                    onChange={(value) => setAttributes({ temperature: value })}
                                    min={0}
                                    max={2}
                                    step={0.1}
                                    help={__('Controls randomness: 0 is deterministic, higher values are more creative.', 'ubc-llm-chat')}
                                />

                                <TextareaControl
                                    label={__('System Prompt', 'ubc-llm-chat')}
                                    value={attributes.systemprompt}
                                    onChange={(value) => setAttributes({ systemprompt: value })}
                                    help={__('Set a system prompt for the LLM.', 'ubc-llm-chat')}
                                />
                            </>
                        )}
                    </PanelBody>

                    <PanelBody title={__('User Settings', 'ubc-llm-chat')} initialOpen={false}>
                        <SelectControl
                            label={__('Minimum User Role', 'ubc-llm-chat')}
                            value={attributes.minimum_user_role}
                            options={userRoles}
                            onChange={(value) => setAttributes({ minimum_user_role: value })}
                            help={__('Minimum user role required to use the chat.', 'ubc-llm-chat')}
                        />

                        <RangeControl
                            label={__('Max Messages Per Conversation', 'ubc-llm-chat')}
                            value={attributes.maxmessages}
                            onChange={(value) => setAttributes({ maxmessages: value })}
                            min={1}
                            max={100}
                            step={1}
                            help={__('Maximum number of messages allowed in a single conversation.', 'ubc-llm-chat')}
                        />

                        <RangeControl
                            label={__('Max Conversations', 'ubc-llm-chat')}
                            value={attributes.maxconversations}
                            onChange={(value) => setAttributes({ maxconversations: value })}
                            min={1}
                            max={50}
                            step={1}
                            help={__('Maximum number of conversations a user can have.', 'ubc-llm-chat')}
                        />
                    </PanelBody>

                    {/* The debug mode toggle is now added to the core Advanced panel by the middleware below */}
                </InspectorControls>

                {/* Add debug mode toggle to WordPress core Advanced panel */}
                <InspectorControls group="advanced">
                    <ToggleControl
                        label={__('Debug Mode', 'ubc-llm-chat')}
                        checked={attributes.debug_mode}
                        onChange={(value) => setAttributes({ debug_mode: value })}
                        help={__('Enable debug mode to see additional information in the console.', 'ubc-llm-chat')}
                    />
                </InspectorControls>

                <div {...blockProps}>
                    {isLoading ? (
                        <div className="ubc-llm-chat-block-loading">
                            <Spinner />
                            <p>{__('Loading LLM chat settings...', 'ubc-llm-chat')}</p>
                        </div>
                    ) : error ? (
                        <div className="ubc-llm-chat-block-error">
                            <p>{error}</p>
                        </div>
                    ) : (
                        <div className="ubc-llm-chat-block-preview">
                            <div className="ubc-llm-chat-block-header">
                                <h3 className="ubc-llm-chat-block-title">
                                    {__('UBC LLM Chat', 'ubc-llm-chat')}
                                    {attributes.debug_mode && <span className="ubc-llm-chat-debug-badge">{__('Debug Mode', 'ubc-llm-chat')}</span>}
                                </h3>
                                <div className="ubc-llm-chat-block-subtitle">
                                    <span className="ubc-llm-chat-service">
                                        {attributes.llmservice
                                            ? attributes.llmservice.charAt(0).toUpperCase() + attributes.llmservice.slice(1)
                                            : __('Default Service', 'ubc-llm-chat')}
                                    </span>
                                    {attributes.llm && <span className="ubc-llm-chat-model"> / {attributes.llm}</span>}
                                </div>
                            </div>

                            <div className="ubc-llm-chat-preview-container">
                                {/* Conversation list */}
                                <div className="ubc-llm-chat-preview-sidebar">
                                    <div className="ubc-llm-chat-preview-new-chat">
                                        <span className="ubc-llm-chat-preview-button">+ {__('New Chat', 'ubc-llm-chat')}</span>
                                    </div>
                                    <div className="ubc-llm-chat-preview-conversations">
                                        <div className="ubc-llm-chat-preview-conversation ubc-llm-chat-preview-active">
                                            <span className="ubc-llm-chat-preview-conversation-title">{__('AI Background Research', 'ubc-llm-chat')}</span>
                                            <span className="ubc-llm-chat-preview-conversation-time">{__('Today', 'ubc-llm-chat')}</span>
                                        </div>
                                        <div className="ubc-llm-chat-preview-conversation">
                                            <span className="ubc-llm-chat-preview-conversation-title">{__('Course Planning', 'ubc-llm-chat')}</span>
                                            <span className="ubc-llm-chat-preview-conversation-time">{__('Yesterday', 'ubc-llm-chat')}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Main chat area */}
                                <div className="ubc-llm-chat-preview-main">
                                    <div className="ubc-llm-chat-preview-messages">
                                        <div className="ubc-llm-chat-preview-message ubc-llm-chat-preview-user-message">
                                            <div className="ubc-llm-chat-preview-message-content">
                                                <p>{__('Can you tell me about recent advancements in AI research?', 'ubc-llm-chat')}</p>
                                            </div>
                                        </div>
                                        <div className="ubc-llm-chat-preview-message ubc-llm-chat-preview-ai-message">
                                            <div className="ubc-llm-chat-preview-message-content">
                                                <p>{__('Recent AI advancements include multimodal models like GPT-4o that can process text, images, and audio simultaneously...', 'ubc-llm-chat')}</p>
                                            </div>
                                        </div>
                                        <div className="ubc-llm-chat-preview-message ubc-llm-chat-preview-user-message">
                                            <div className="ubc-llm-chat-preview-message-content">
                                                <p>{__('How might these be applied in education?', 'ubc-llm-chat')}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="ubc-llm-chat-preview-input-area">
                                        <div className="ubc-llm-chat-preview-input">
                                            <div className="ubc-llm-chat-preview-textarea">{__('Type your message...', 'ubc-llm-chat')}</div>
                                            <div className="ubc-llm-chat-preview-send-button">→</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="ubc-llm-chat-block-footer">
                                <div className="ubc-llm-chat-block-settings-badge">
                                    <span><strong>{__('Max Messages:', 'ubc-llm-chat')}</strong> {attributes.maxmessages}</span>
                                    <span><strong>{__('Max Conversations:', 'ubc-llm-chat')}</strong> {attributes.maxconversations}</span>
                                    <span><strong>{__('Temperature:', 'ubc-llm-chat')}</strong> {attributes.temperature}</span>
                                </div>
                                {attributes.minimum_user_role && (
                                    <div className="ubc-llm-chat-block-role-badge">
                                        {__('Minimum Role:', 'ubc-llm-chat')} {attributes.minimum_user_role}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </>
        );
    },

    /**
     * Save function
     *
     * We're using a dynamic block, so we don't need to save anything here.
     * The output is handled by the PHP render callback.
     */
    save: () => {
        return null;
    },
});