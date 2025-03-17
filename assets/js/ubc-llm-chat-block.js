/**
 * UBC LLM Chat Block
 *
 * Registers a block for the UBC LLM Chat plugin.
 */

( function( wp ) {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls } = wp.blockEditor;
	const {
		PanelBody,
		TextControl,
		SelectControl,
		RangeControl,
		ToggleControl,
		TextareaControl
	} = wp.components;
	const { __ } = wp.i18n;
	const { Fragment } = wp.element;

	/**
	 * Register the block
	 */
	registerBlockType( 'ubc-llm-chat/chat', {
		title: __( 'UBC LLM Chat', 'ubc-llm-chat' ),
		description: __( 'Add an LLM chat interface to your page.', 'ubc-llm-chat' ),
		category: 'widgets',
		icon: 'format-chat',
		keywords: [ 'chat', 'ai', 'llm', 'chatgpt', 'ollama' ],

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
		edit: function( props ) {
			const { attributes, setAttributes } = props;

			// Get available LLM services
			const llmServices = [
				{ label: __( 'Default (from settings)', 'ubc-llm-chat' ), value: '' },
				{ label: __( 'OpenAI', 'ubc-llm-chat' ), value: 'openai' },
				{ label: __( 'Ollama', 'ubc-llm-chat' ), value: 'ollama' },
			];

			// Get available models (this would ideally be dynamic based on the selected service)
			const openaiModels = [
				{ label: __( 'Default (from settings)', 'ubc-llm-chat' ), value: '' },
				{ label: __( 'GPT-4o', 'ubc-llm-chat' ), value: 'gpt-4o' },
				{ label: __( 'GPT-4o-mini', 'ubc-llm-chat' ), value: 'gpt-4o-mini' },
				{ label: __( 'GPT-3.5 Turbo', 'ubc-llm-chat' ), value: 'gpt-3.5-turbo' },
			];

			const ollamaModels = [
				{ label: __( 'Default (from settings)', 'ubc-llm-chat' ), value: '' },
				{ label: __( 'Llama 3', 'ubc-llm-chat' ), value: 'llama3' },
				{ label: __( 'Mistral', 'ubc-llm-chat' ), value: 'mistral' },
			];

			// Get available models based on selected service
			let availableModels = [{ label: __( 'Default (from settings)', 'ubc-llm-chat' ), value: '' }];
			if (attributes.llmservice === 'openai') {
				availableModels = openaiModels;
			} else if (attributes.llmservice === 'ollama') {
				availableModels = ollamaModels;
			}

			// Get available user roles
			const userRoles = [
				{ label: __( 'Default (from settings)', 'ubc-llm-chat' ), value: '' },
				{ label: __( 'Subscriber', 'ubc-llm-chat' ), value: 'subscriber' },
				{ label: __( 'Contributor', 'ubc-llm-chat' ), value: 'contributor' },
				{ label: __( 'Author', 'ubc-llm-chat' ), value: 'author' },
				{ label: __( 'Editor', 'ubc-llm-chat' ), value: 'editor' },
				{ label: __( 'Administrator', 'ubc-llm-chat' ), value: 'administrator' },
			];

			return (
				<Fragment>
					<InspectorControls>
						<PanelBody title={ __( 'LLM Settings', 'ubc-llm-chat' ) } initialOpen={ true }>
							<SelectControl
								label={ __( 'LLM Service', 'ubc-llm-chat' ) }
								value={ attributes.llmservice }
								options={ llmServices }
								onChange={ ( value ) => setAttributes( { llmservice: value } ) }
								help={ __( 'Select the LLM service to use. Leave empty to use the default from settings.', 'ubc-llm-chat' ) }
							/>

							<SelectControl
								label={ __( 'LLM Model', 'ubc-llm-chat' ) }
								value={ attributes.llm }
								options={ availableModels }
								onChange={ ( value ) => setAttributes( { llm: value } ) }
								help={ __( 'Select the LLM model to use. Leave empty to use the default from settings.', 'ubc-llm-chat' ) }
							/>

							<RangeControl
								label={ __( 'Temperature', 'ubc-llm-chat' ) }
								value={ attributes.temperature }
								onChange={ ( value ) => setAttributes( { temperature: value } ) }
								min={ 0 }
								max={ 2 }
								step={ 0.1 }
								help={ __( 'Controls randomness: 0 is deterministic, higher values are more creative.', 'ubc-llm-chat' ) }
							/>

							<TextareaControl
								label={ __( 'System Prompt', 'ubc-llm-chat' ) }
								value={ attributes.systemprompt }
								onChange={ ( value ) => setAttributes( { systemprompt: value } ) }
								help={ __( 'Set a system prompt for the LLM.', 'ubc-llm-chat' ) }
							/>
						</PanelBody>

						<PanelBody title={ __( 'User Settings', 'ubc-llm-chat' ) } initialOpen={ false }>
							<SelectControl
								label={ __( 'Minimum User Role', 'ubc-llm-chat' ) }
								value={ attributes.minimum_user_role }
								options={ userRoles }
								onChange={ ( value ) => setAttributes( { minimum_user_role: value } ) }
								help={ __( 'Minimum user role required to use the chat.', 'ubc-llm-chat' ) }
							/>

							<RangeControl
								label={ __( 'Max Messages Per Conversation', 'ubc-llm-chat' ) }
								value={ attributes.maxmessages }
								onChange={ ( value ) => setAttributes( { maxmessages: value } ) }
								min={ 1 }
								max={ 100 }
								step={ 1 }
								help={ __( 'Maximum number of messages allowed in a single conversation.', 'ubc-llm-chat' ) }
							/>

							<RangeControl
								label={ __( 'Max Conversations', 'ubc-llm-chat' ) }
								value={ attributes.maxconversations }
								onChange={ ( value ) => setAttributes( { maxconversations: value } ) }
								min={ 1 }
								max={ 50 }
								step={ 1 }
								help={ __( 'Maximum number of conversations a user can have.', 'ubc-llm-chat' ) }
							/>
						</PanelBody>

						<PanelBody title={ __( 'Advanced Settings', 'ubc-llm-chat' ) } initialOpen={ false }>
							<ToggleControl
								label={ __( 'Debug Mode', 'ubc-llm-chat' ) }
								checked={ attributes.debug_mode }
								onChange={ ( value ) => setAttributes( { debug_mode: value } ) }
								help={ __( 'Enable debug mode to see additional information in the console.', 'ubc-llm-chat' ) }
							/>
						</PanelBody>
					</InspectorControls>

					<div className="ubc-llm-chat-block-editor">
						<div className="ubc-llm-chat-block-preview">
							<div className="ubc-llm-chat-block-title">
								{ __( 'UBC LLM Chat', 'ubc-llm-chat' ) }
							</div>
							<div className="ubc-llm-chat-block-description">
								{ __( 'This block will display a chat interface on the front end.', 'ubc-llm-chat' ) }
							</div>
							<div className="ubc-llm-chat-block-settings">
								<p><strong>{ __( 'Service:', 'ubc-llm-chat' ) }</strong> { attributes.llmservice || __( 'Default', 'ubc-llm-chat' ) }</p>
								<p><strong>{ __( 'Model:', 'ubc-llm-chat' ) }</strong> { attributes.llm || __( 'Default', 'ubc-llm-chat' ) }</p>
								<p><strong>{ __( 'Temperature:', 'ubc-llm-chat' ) }</strong> { attributes.temperature }</p>
								<p><strong>{ __( 'Max Messages:', 'ubc-llm-chat' ) }</strong> { attributes.maxmessages }</p>
								<p><strong>{ __( 'Max Conversations:', 'ubc-llm-chat' ) }</strong> { attributes.maxconversations }</p>
							</div>
						</div>
					</div>
				</Fragment>
			);
		},

		/**
		 * Save function
		 *
		 * We're using a dynamic block, so we don't need to save anything here.
		 * The output is handled by the PHP render callback.
		 */
		save: function() {
			return null;
		},
	} );
} )( window.wp );
