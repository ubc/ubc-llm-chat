/**
 * Admin JavaScript for UBC LLM Chat plugin.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle OpenAI fields based on the checkbox state
    const openaiEnabledCheckbox = document.getElementById('openai_enabled');
    const openaiApiKeyField = document.getElementById('openai_api_key');
    const fetchOpenaiModelsButton = document.getElementById('fetch_openai_models');
    const testOpenaiConnectionButton = document.getElementById('test_openai_connection');

    if (openaiEnabledCheckbox && openaiApiKeyField && fetchOpenaiModelsButton) {
        openaiEnabledCheckbox.addEventListener('change', function() {
            openaiApiKeyField.disabled = !this.checked;
            fetchOpenaiModelsButton.disabled = !this.checked;

            if (testOpenaiConnectionButton) {
                testOpenaiConnectionButton.style.display = this.checked ? 'inline-block' : 'none';
            }
        });
    }

    // Toggle Ollama fields based on the checkbox state
    const ollamaEnabledCheckbox = document.getElementById('ollama_enabled');
    const ollamaUrlField = document.getElementById('ollama_url');
    const ollamaApiKeyField = document.getElementById('ollama_api_key');
    const fetchOllamaModelsButton = document.getElementById('fetch_ollama_models');
    const testOllamaConnectionButton = document.getElementById('test_ollama_connection');

    if (ollamaEnabledCheckbox && ollamaUrlField && ollamaApiKeyField && fetchOllamaModelsButton) {
        ollamaEnabledCheckbox.addEventListener('change', function() {
            ollamaUrlField.disabled = !this.checked;
            ollamaApiKeyField.disabled = !this.checked;
            fetchOllamaModelsButton.disabled = !this.checked;

            if (testOllamaConnectionButton) {
                testOllamaConnectionButton.style.display = this.checked ? 'inline-block' : 'none';
            }
        });
    }

    // Test OpenAI connection
    if (testOpenaiConnectionButton) {
        testOpenaiConnectionButton.addEventListener('click', function() {
            const resultSpan = document.getElementById('openai_connection_result');
            resultSpan.textContent = 'Testing connection...';
            resultSpan.className = 'connection-result';

            // AJAX call to test connection
            const data = {
                action: 'ubc_llm_chat_test_openai_connection',
                nonce: ubc_llm_chat_admin.nonce
            };

            fetch(ubc_llm_chat_admin.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultSpan.textContent = 'Connection successful!';
                    resultSpan.className = 'connection-result success';
                } else {
                    resultSpan.textContent = 'Connection failed: ' + data.data;
                    resultSpan.className = 'connection-result error';
                }
            })
            .catch(error => {
                resultSpan.textContent = 'Connection failed: ' + error.message;
                resultSpan.className = 'connection-result error';
            });
        });
    }

    // Test Ollama connection
    if (testOllamaConnectionButton) {
        testOllamaConnectionButton.addEventListener('click', function() {
            const resultSpan = document.getElementById('ollama_connection_result');
            resultSpan.textContent = 'Testing connection...';
            resultSpan.className = 'connection-result';

            // AJAX call to test connection
            const data = {
                action: 'ubc_llm_chat_test_ollama_connection',
                nonce: ubc_llm_chat_admin.nonce,
                url: ollamaUrlField.value
            };

            fetch(ubc_llm_chat_admin.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log( 'in then', data );
                if (data.success) {
                    resultSpan.textContent = 'Connection successful!';
                    resultSpan.className = 'connection-result success';
                } else {
                    resultSpan.textContent = 'Connection failed: ' + data.data;
                    resultSpan.className = 'connection-result error';
                }
            })
            .catch(error => {
                console.log( 'in catch', error );
                resultSpan.textContent = 'Connection failed: ' + error.message;
                resultSpan.className = 'connection-result error';
            });
        });
    }

    // Fetch OpenAI models
    if (fetchOpenaiModelsButton) {
        fetchOpenaiModelsButton.addEventListener('click', function() {
            const resultSpan = document.getElementById('openai_models_result');
            const modelsContainer = document.getElementById('openai_models_container');

            resultSpan.textContent = 'Fetching models...';
            resultSpan.className = 'models-result';

            // AJAX call to fetch models
            const data = {
                action: 'ubc_llm_chat_fetch_openai_models',
                nonce: ubc_llm_chat_admin.nonce
            };

            fetch(ubc_llm_chat_admin.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultSpan.textContent = 'Models fetched successfully!';
                    resultSpan.className = 'models-result success';

                    // Get current selected models
                    const currentModels = {};
                    const existingCheckboxes = modelsContainer.querySelectorAll('input[type="checkbox"]');

                    existingCheckboxes.forEach(checkbox => {
                        // Extract model ID from the name attribute
                        const match = checkbox.name.match(/\[openai_models\]\[([^\]]+)\]/);
                        if (match && match[1]) {
                            // Store checked state by model ID
                            currentModels[match[1]] = checkbox.checked;
                        }
                    });

                    // Create new HTML with proper structure
                    let html = '<p>' + (window.ubc_llm_chat_admin && window.ubc_llm_chat_admin.i18n ?
                                        window.ubc_llm_chat_admin.i18n.available_models :
                                        'Available Models:') + '</p><ul>';

                    data.data.forEach(model => {
                        // Check if this model was previously selected, default to checked if it's a new model
                        const isChecked = currentModels.hasOwnProperty(model.id) ? currentModels[model.id] : true;

                        html += `
                            <li>
                                <label>
                                    <input type="checkbox" name="ubc_llm_chat_settings[openai_models][${model.id}]" value="${model.id}" ${isChecked ? 'checked' : ''} />
                                    ${model.id}
                                </label>
                            </li>
                        `;
                    });

                    html += '</ul>';
                    modelsContainer.innerHTML = html;
                } else {
                    resultSpan.textContent = 'Failed to fetch models: ' + data.data;
                    resultSpan.className = 'models-result error';
                }
            })
            .catch(error => {
                resultSpan.textContent = 'Failed to fetch models: ' + error.message;
                resultSpan.className = 'models-result error';
            });
        });
    }

    // Fetch Ollama models
    if (fetchOllamaModelsButton) {
        fetchOllamaModelsButton.addEventListener('click', function() {
            const resultSpan = document.getElementById('ollama_models_result');
            const modelsContainer = document.getElementById('ollama_models_container');

            resultSpan.textContent = 'Fetching models...';
            resultSpan.className = 'models-result';

            // AJAX call to fetch models
            const data = {
                action: 'ubc_llm_chat_fetch_ollama_models',
                nonce: ubc_llm_chat_admin.nonce,
                url: ollamaUrlField.value
            };

            fetch(ubc_llm_chat_admin.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultSpan.textContent = 'Models fetched successfully!';
                    resultSpan.className = 'models-result success';

                    // Get current selected models
                    const currentModels = {};
                    const existingCheckboxes = modelsContainer.querySelectorAll('input[type="checkbox"]');

                    existingCheckboxes.forEach(checkbox => {
                        // Extract model ID from the name attribute
                        const match = checkbox.name.match(/\[ollama_models\]\[([^\]]+)\]/);
                        if (match && match[1]) {
                            // Store checked state by model ID
                            currentModels[match[1]] = checkbox.checked;
                        }
                    });

                    // Create new HTML with proper structure
                    let html = '<p>' + (window.ubc_llm_chat_admin && window.ubc_llm_chat_admin.i18n ?
                                        window.ubc_llm_chat_admin.i18n.available_models :
                                        'Available Models:') + '</p><ul>';

                    data.data.forEach(model => {
                        // Check if this model was previously selected, default to checked if it's a new model
                        const isChecked = currentModels.hasOwnProperty(model.name) ? currentModels[model.name] : true;

                        html += `
                            <li>
                                <label>
                                    <input type="checkbox" name="ubc_llm_chat_settings[ollama_models][${model.name}]" value="${model.name}" ${isChecked ? 'checked' : ''} />
                                    ${model.name}
                                </label>
                            </li>
                        `;
                    });

                    html += '</ul>';
                    modelsContainer.innerHTML = html;
                } else {
                    resultSpan.textContent = 'Failed to fetch models: ' + data.data;
                    resultSpan.className = 'models-result error';
                }
            })
            .catch(error => {
                resultSpan.textContent = 'Failed to fetch models: ' + error.message;
                resultSpan.className = 'models-result error';
            });
        });
    }
});
