<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat
 */

namespace UBC\LLMChat;

/**
 * The core plugin class.
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      UBC_LLM_Chat_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_api_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - UBC_LLM_Chat_Loader. Orchestrates the hooks of the plugin.
	 * - UBC_LLM_Chat_i18n. Defines internationalization functionality.
	 * - UBC_LLM_Chat_Admin. Defines all hooks for the admin area.
	 * - UBC_LLM_Chat_Public. Defines all hooks for the public side of the site.
	 * - UBC_LLM_Chat_Debug. Defines debug functionality.
	 * - UBC_LLM_Chat_API. Defines REST API functionality.
	 * - UBC_LLM_Chat_Usage. Defines usage functionality.
	 * - UBC_LLM_Chat_API_Key_Manager. Defines API key management functionality.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/class-ubc-llm-chat-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/class-ubc-llm-chat-i18n.php';

		/**
		 * The class responsible for defining debug functionality.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/class-ubc-llm-chat-debug.php';

		/**
		 * The class responsible for defining filters functionality.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/class-ubc-llm-chat-filters.php';

		/**
		 * The class responsible for tracking usage.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/class-ubc-llm-chat-usage.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/admin/class-ubc-llm-chat-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/public/class-ubc-llm-chat-public.php';

		/**
		 * The class responsible for rendering the chat interface.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/public/class-ubc-llm-chat-template.php';

		/**
		 * The class responsible for handling shortcode functionality.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/public/class-ubc-llm-chat-shortcode.php';

		/**
		 * The class responsible for handling block functionality.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/public/class-ubc-llm-chat-block.php';

		/**
		 * The class responsible for defining REST API functionality.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/class-ubc-llm-chat-api.php';

		/**
		 * The class responsible for API authentication.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/class-ubc-llm-chat-api-auth.php';

		/**
		 * The class responsible for API utilities.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/class-ubc-llm-chat-api-utils.php';

		/**
		 * The class responsible for API key management.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/class-ubc-llm-chat-api-key-manager.php';

		/**
		 * The class responsible for API routes registration.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/class-ubc-llm-chat-api-routes.php';

		/**
		 * The class responsible for conversation management.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/class-ubc-llm-chat-conversation-controller.php';

		/**
		 * The class responsible for message management.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/class-ubc-llm-chat-message-controller.php';

		/**
		 * The service factory for LLM services.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/services/class-ubc-llm-chat-service-factory.php';

		/**
		 * The base service interface.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/services/class-ubc-llm-chat-service-base.php';

		/**
		 * The OpenAI service.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/services/class-ubc-llm-chat-openai-service.php';

		/**
		 * The Ollama service.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/services/class-ubc-llm-chat-ollama-service.php';

		/**
		 * The Test service.
		 */
		require_once UBC_LLM_CHAT_PATH . 'includes/api/services/class-ubc-llm-chat-test-service.php';

		$this->loader = new UBC_LLM_Chat_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the UBC_LLM_Chat_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new UBC_LLM_Chat_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Admin\UBC_LLM_Chat_Admin();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_settings_page' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Public\UBC_LLM_Chat_Public();

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Register shortcode.
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// Register block.
		$this->loader->add_action( 'init', $plugin_public, 'register_blocks' );
	}

	/**
	 * Register all of the hooks related to the REST API functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_api_hooks() {
		$plugin_api = new API\UBC_LLM_Chat_API();

		$this->loader->add_action( 'rest_api_init', $plugin_api, 'register_routes' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    UBC_LLM_Chat_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}
