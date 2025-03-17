# UBC LLM Chat Block Development

This document provides instructions for developing and working with the UBC LLM Chat block.

## Prerequisites

-   Node.js and npm installed (Node.js 14+ recommended)

## Getting Started

1. Install dependencies:

    ```
    npm install
    ```

2. Build the block:

    ```
    npm run build
    ```

3. Development mode (watch for changes):
    ```
    npm run dev
    ```

## Block Structure

The block source code is organized as follows:

-   `src/index.js` - Main entry point
-   `src/blocks/ubc-llm-chat/index.js` - Block registration and React components
-   `src/blocks/ubc-llm-chat/style.scss` - Styles for both editor and front-end
-   `src/blocks/ubc-llm-chat/editor.scss` - Editor-only styles

## Dynamic Settings

The block dynamically fetches settings from the WordPress REST API endpoint `/ubc-llm-chat/v1/settings` to:

1. Get a list of enabled LLM services
2. Get available models for each enabled service
3. Apply global settings as defaults (rate limits, max conversations, etc.)

This ensures that the block UI only shows options that are actually available based on the admin settings.

## Build Process

The WordPress scripts package handles the build process, which:

1. Transpiles modern JavaScript (ES6+) to be compatible with browsers
2. Compiles SCSS to CSS
3. Bundles all dependencies
4. Minifies the output for production

The build output is placed in the `build` directory and includes:

-   `index.js` - JavaScript bundle
-   `index.css` - CSS styles
-   Asset files for WordPress

## WordPress Integration

The block is registered and rendered via the PHP class `UBC_LLM_Chat_Block` in `includes/public/class-ubc-llm-chat-block.php`.

The block is server-rendered using the `render` method, which creates a unique instance of the chat interface
for each block instance on the page.

The REST API endpoint for settings is implemented in `includes/api/class-ubc-llm-chat-message-controller.php`.

## Block Features

-   Dynamic dropdowns for LLM service and model selection based on enabled options
-   System prompt input for customizing the AI behavior
-   Temperature control for adjusting response randomness
-   User role access control
-   Message and conversation limits
-   Debug mode toggle (in the core WordPress Advanced panel)

## Best Practices

1. Keep the block focused on its core functionality
2. Maintain proper separation between the block editor UI and the server-side rendering
3. Use WordPress hooks for extensibility
4. Follow WordPress coding standards
5. Keep dependencies minimal

## Updating the Block

When making changes to the block:

1. Run `npm run dev` to start the development server
2. Make your changes to the source files
3. Test the block in the WordPress editor
4. Run `npm run build` to create production files when finished

## Troubleshooting

If you encounter issues:

1. Check the browser console for JavaScript errors
2. Verify that the build process completed successfully
3. Clear the browser cache
4. Ensure WordPress debug mode is enabled
5. Check PHP error logs
6. Verify that REST API endpoints are accessible
