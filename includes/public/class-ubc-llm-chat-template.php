<?php
/**
 * The chat interface template class.
 *
 * This class handles rendering the chat interface HTML structure.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Public
 */

namespace UBC\LLMChat\Public;

/**
 * The chat interface template class.
 *
 * @since      1.0.0
 */
class UBC_LLM_Chat_Template {

	/**
	 * The chat instance ID.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $instance_id    The chat instance ID.
	 */
	private $instance_id;

	/**
	 * The chat settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    The chat settings.
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $instance_id    The chat instance ID.
	 * @param    array  $settings       The chat settings.
	 */
	public function __construct( $instance_id, $settings ) {
		$this->instance_id = $instance_id;
		$this->settings    = $settings;

		// Log instance creation for debugging.
		\UBC\LLMChat\UBC_LLM_Chat_Debug::info(
			sprintf(
				'Chat instance created: %s with settings: %s',
				$instance_id,
				wp_json_encode( $settings )
			),
			$settings
		);
	}

	/**
	 * Render the chat interface.
	 *
	 * @since    1.0.0
	 * @return   string    The chat interface HTML.
	 */
	public function render() {
		// Check if user is logged in and has the required role.
		if ( ! is_user_logged_in() ) {
			return $this->render_login_prompt();
		}

		// Get the minimum required role from instance settings or global settings.
		$minimum_role = isset( $this->settings['minimum_user_role'] ) ? $this->settings['minimum_user_role'] : 'subscriber';

		// Get the current user.
		$user = wp_get_current_user();

		// Define role hierarchy.
		$roles_hierarchy = array(
			'subscriber'    => 0,
			'contributor'   => 1,
			'author'        => 2,
			'editor'        => 3,
			'administrator' => 4,
		);

		// Get the minimum role level.
		$minimum_role_level = isset( $roles_hierarchy[ $minimum_role ] ) ? $roles_hierarchy[ $minimum_role ] : 0;

		// Check if user has a role with sufficient permissions.
		$has_role = false;
		foreach ( $user->roles as $role ) {
			if ( isset( $roles_hierarchy[ $role ] ) && $roles_hierarchy[ $role ] >= $minimum_role_level ) {
				$has_role = true;
				break;
			}
		}

		if ( ! $has_role ) {
			return sprintf(
				'<div class="ubc-llm-chat-error">%s</div>',
				esc_html__( 'You do not have sufficient permissions to access the chat interface.', 'ubc-llm-chat' )
			);
		}

		// Start output buffering.
		ob_start();

		// Render the chat container.
		$this->render_chat_container();

		// Return the buffered output.
		return ob_get_clean();
	}

	/**
	 * Render the login prompt.
	 *
	 * @since    1.0.0
	 * @return   string    The login prompt HTML.
	 */
	private function render_login_prompt() {
		ob_start();
		?>
		<div class="ubc-llm-chat-login-prompt">
			<p><?php esc_html_e( 'You must be logged in to use the chat interface.', 'ubc-llm-chat' ); ?></p>
			<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="ubc-llm-chat-login-button">
				<?php esc_html_e( 'Log In', 'ubc-llm-chat' ); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the chat container.
	 *
	 * @since    1.0.0
	 */
	private function render_chat_container() {
		// Get the container classes.
		$container_classes = array(
			'ubc-llm-chat-container',
			'ubc-llm-chat-instance-' . $this->instance_id,
		);

		// Apply filter to allow plugins to modify the container classes.
		$container_classes = apply_filters( 'ubc_llm_chat_interface_classes', $container_classes, $this->instance_id );

		// Convert array to string.
		$container_classes = implode( ' ', array_map( 'sanitize_html_class', $container_classes ) );

		// Get the data attributes.
		$data_attributes = array(
			'data-instance-id="' . esc_attr( $this->instance_id ) . '"',
			'data-llm-service="' . esc_attr( $this->settings['llmservice'] ?? '' ) . '"',
			'data-llm-model="' . esc_attr( $this->settings['llm'] ?? '' ) . '"',
			'data-max-messages="' . esc_attr( $this->settings['maxmessages'] ?? 20 ) . '"',
			'data-max-conversations="' . esc_attr( $this->settings['maxconversations'] ?? 10 ) . '"',
			'data-temperature="' . esc_attr( $this->settings['temperature'] ?? 0.7 ) . '"',
			'data-system-prompt="' . esc_attr( $this->settings['systemprompt'] ?? '' ) . '"',
		);

		// Convert array to string.
		$data_attributes = implode( ' ', $data_attributes );
		?>
		<div class="<?php echo esc_attr( $container_classes ); ?>" <?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each attribute is escaped individually above. ?>>
			<div class="ubc-llm-chat-wrapper">
				<?php $this->render_conversation_list(); ?>
				<div class="ubc-llm-chat-main">
					<?php $this->render_chat_area(); ?>
					<?php $this->render_input_area(); ?>
				</div>
			</div>
			<?php $this->render_modals(); ?>
			<?php $this->render_notifications(); ?>
		</div>
		<?php
	}

	/**
	 * Render the conversation list.
	 *
	 * @since    1.0.0
	 */
	private function render_conversation_list() {
		?>
		<div class="ubc-llm-chat-conversation-list">
			<div class="ubc-llm-chat-conversation-list-header">
				<h2 class="ubc-llm-chat-conversation-list-title"><?php esc_html_e( 'Conversations', 'ubc-llm-chat' ); ?></h2>
				<button type="button" class="ubc-llm-chat-new-conversation-button" aria-label="<?php esc_attr_e( 'New Conversation', 'ubc-llm-chat' ); ?>">
					<span class="ubc-llm-chat-icon ubc-llm-chat-icon-plus"></span>
					<span class="ubc-llm-chat-button-text"><?php esc_html_e( 'New Chat', 'ubc-llm-chat' ); ?></span>
				</button>
				<button type="button" class="ubc-llm-chat-toggle-conversation-list-button" aria-label="<?php esc_attr_e( 'Toggle Conversation List', 'ubc-llm-chat' ); ?>" aria-expanded="false" aria-controls="ubc-llm-chat-conversation-list-items-<?php echo esc_attr( $this->instance_id ); ?>">
					<span class="ubc-llm-chat-icon ubc-llm-chat-icon-menu"></span>
				</button>
			</div>
			<div id="ubc-llm-chat-conversation-list-items-<?php echo esc_attr( $this->instance_id ); ?>" class="ubc-llm-chat-conversation-list-items" aria-label="<?php esc_attr_e( 'Conversation List', 'ubc-llm-chat' ); ?>">
				<!-- Conversations will be populated dynamically via JavaScript -->
				<div class="ubc-llm-chat-conversation-list-empty">
					<p><?php esc_html_e( 'No conversations yet.', 'ubc-llm-chat' ); ?></p>
					<p><?php esc_html_e( 'Start a new chat to begin.', 'ubc-llm-chat' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the chat area.
	 *
	 * @since    1.0.0
	 */
	private function render_chat_area() {
		?>
		<div class="ubc-llm-chat-area" aria-live="polite">
			<div class="ubc-llm-chat-messages">
				<!-- Messages will be populated dynamically via JavaScript -->
				<div class="ubc-llm-chat-welcome">
					<h2><?php esc_html_e( 'Welcome to UBC LLM Chat', 'ubc-llm-chat' ); ?></h2>
					<p><?php esc_html_e( 'Start a new conversation or select an existing one from the sidebar.', 'ubc-llm-chat' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the input area.
	 *
	 * @since    1.0.0
	 */
	private function render_input_area() {
		?>
		<div class="ubc-llm-chat-input-area">
			<form class="ubc-llm-chat-form" action="#" method="post">
				<div class="ubc-llm-chat-actions">
					<button type="button" class="ubc-llm-chat-export-button" aria-label="<?php esc_attr_e( 'Export Conversation', 'ubc-llm-chat' ); ?>">
						<span class="ubc-llm-chat-icon ubc-llm-chat-icon-download"></span>
						<span class="ubc-llm-chat-button-text"><?php esc_html_e( 'Export', 'ubc-llm-chat' ); ?></span>
					</button>
					<button type="button" class="ubc-llm-chat-rename-button" aria-label="<?php esc_attr_e( 'Rename Conversation', 'ubc-llm-chat' ); ?>">
						<span class="ubc-llm-chat-icon ubc-llm-chat-icon-edit"></span>
						<span class="ubc-llm-chat-button-text"><?php esc_html_e( 'Rename', 'ubc-llm-chat' ); ?></span>
					</button>
					<button type="button" class="ubc-llm-chat-delete-button" aria-label="<?php esc_attr_e( 'Delete Conversation', 'ubc-llm-chat' ); ?>">
						<span class="ubc-llm-chat-icon ubc-llm-chat-icon-trash"></span>
						<span class="ubc-llm-chat-button-text"><?php esc_html_e( 'Delete', 'ubc-llm-chat' ); ?></span>
					</button>
				</div>
				<div class="ubc-llm-chat-textarea-wrapper">
					<textarea
						name="ubc-llm-chat-input"
						id="ubc-llm-chat-input-<?php echo esc_attr( $this->instance_id ); ?>"
						class="ubc-llm-chat-input"
						placeholder="<?php esc_attr_e( 'Type your message here...', 'ubc-llm-chat' ); ?>"
						rows="1"
						aria-label="<?php esc_attr_e( 'Message Input', 'ubc-llm-chat' ); ?>"
						required
					></textarea>
				</div>
				<div class="ubc-llm-chat-submit-wrapper">
					<div class="ubc-llm-chat-rate-limit-message" aria-live="polite"></div>
					<button type="submit" class="ubc-llm-chat-submit-button" aria-label="<?php esc_attr_e( 'Send Message', 'ubc-llm-chat' ); ?>">
						<span class="ubc-llm-chat-icon ubc-llm-chat-icon-send"></span>
						<span class="ubc-llm-chat-button-text"><?php esc_html_e( 'Send', 'ubc-llm-chat' ); ?></span>
					</button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the modals.
	 *
	 * @since    1.0.0
	 */
	private function render_modals() {
		?>
		<!-- Delete Confirmation Modal -->
		<div id="ubc-llm-chat-delete-modal-<?php echo esc_attr( $this->instance_id ); ?>" class="ubc-llm-chat-modal" aria-hidden="true" role="dialog" aria-labelledby="ubc-llm-chat-delete-modal-title-<?php echo esc_attr( $this->instance_id ); ?>" aria-describedby="ubc-llm-chat-delete-modal-description-<?php echo esc_attr( $this->instance_id ); ?>">
			<div class="ubc-llm-chat-modal-overlay" tabindex="-1" data-close-modal></div>
			<div class="ubc-llm-chat-modal-container" role="document">
				<div class="ubc-llm-chat-modal-header">
					<h3 id="ubc-llm-chat-delete-modal-title-<?php echo esc_attr( $this->instance_id ); ?>" class="ubc-llm-chat-modal-title">
						<?php esc_html_e( 'Delete Conversation', 'ubc-llm-chat' ); ?>
					</h3>
					<button type="button" class="ubc-llm-chat-modal-close" aria-label="<?php esc_attr_e( 'Close', 'ubc-llm-chat' ); ?>" data-close-modal>
						<span class="ubc-llm-chat-icon ubc-llm-chat-icon-close"></span>
					</button>
				</div>
				<div class="ubc-llm-chat-modal-content">
					<p id="ubc-llm-chat-delete-modal-description-<?php echo esc_attr( $this->instance_id ); ?>">
						<?php esc_html_e( 'Are you sure you want to delete this conversation? This action cannot be undone.', 'ubc-llm-chat' ); ?>
					</p>
				</div>
				<div class="ubc-llm-chat-modal-footer">
					<button type="button" class="ubc-llm-chat-button ubc-llm-chat-button-secondary" data-close-modal>
						<?php esc_html_e( 'Cancel', 'ubc-llm-chat' ); ?>
					</button>
					<button type="button" class="ubc-llm-chat-button ubc-llm-chat-button-danger ubc-llm-chat-delete-confirm-button">
						<?php esc_html_e( 'Delete', 'ubc-llm-chat' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Rename Conversation Modal -->
		<div id="ubc-llm-chat-rename-modal-<?php echo esc_attr( $this->instance_id ); ?>" class="ubc-llm-chat-modal" aria-hidden="true" role="dialog" aria-labelledby="ubc-llm-chat-rename-modal-title-<?php echo esc_attr( $this->instance_id ); ?>">
			<div class="ubc-llm-chat-modal-overlay" tabindex="-1" data-close-modal></div>
			<div class="ubc-llm-chat-modal-container" role="document">
				<div class="ubc-llm-chat-modal-header">
					<h3 id="ubc-llm-chat-rename-modal-title-<?php echo esc_attr( $this->instance_id ); ?>" class="ubc-llm-chat-modal-title">
						<?php esc_html_e( 'Rename Conversation', 'ubc-llm-chat' ); ?>
					</h3>
					<button type="button" class="ubc-llm-chat-modal-close" aria-label="<?php esc_attr_e( 'Close', 'ubc-llm-chat' ); ?>" data-close-modal>
						<span class="ubc-llm-chat-icon ubc-llm-chat-icon-close"></span>
					</button>
				</div>
				<div class="ubc-llm-chat-modal-content">
					<form class="ubc-llm-chat-rename-form">
						<div class="ubc-llm-chat-form-group">
							<label for="ubc-llm-chat-rename-input-<?php echo esc_attr( $this->instance_id ); ?>" class="ubc-llm-chat-form-label">
								<?php esc_html_e( 'New Name', 'ubc-llm-chat' ); ?>
							</label>
							<input type="text" id="ubc-llm-chat-rename-input-<?php echo esc_attr( $this->instance_id ); ?>" class="ubc-llm-chat-rename-input" required>
						</div>
					</form>
				</div>
				<div class="ubc-llm-chat-modal-footer">
					<button type="button" class="ubc-llm-chat-button ubc-llm-chat-button-secondary" data-close-modal>
						<?php esc_html_e( 'Cancel', 'ubc-llm-chat' ); ?>
					</button>
					<button type="button" class="ubc-llm-chat-button ubc-llm-chat-button-primary ubc-llm-chat-rename-confirm-button">
						<?php esc_html_e( 'Rename', 'ubc-llm-chat' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the notifications.
	 *
	 * @since    1.0.0
	 */
	private function render_notifications() {
		?>
		<div class="ubc-llm-chat-notifications" aria-live="polite"></div>
		<?php
	}

	/**
	 * Get message templates for JavaScript.
	 *
	 * @since    1.0.0
	 * @return   array    The message templates.
	 */
	public static function get_message_templates() {
		return array(
			'conversation_item'      => '
				<div class="ubc-llm-chat-conversation-item" data-conversation-id="{{id}}">
					<button type="button" class="ubc-llm-chat-conversation-button" aria-label="{{title}}">
						<span class="ubc-llm-chat-conversation-title">{{title}}</span>
					</button>
				</div>
			',
			'user_message'           => '
				<div class="ubc-llm-chat-message ubc-llm-chat-message-user">
					<div class="ubc-llm-chat-message-header">
						<div class="ubc-llm-chat-message-avatar">
							<span class="ubc-llm-chat-icon ubc-llm-chat-icon-user"></span>
						</div>
						<div class="ubc-llm-chat-message-meta">
							<span class="ubc-llm-chat-message-sender">' . esc_html__( 'You', 'ubc-llm-chat' ) . '</span>
							<span class="ubc-llm-chat-message-time">{{time}}</span>
						</div>
					</div>
					<div class="ubc-llm-chat-message-content">{{content}}</div>
				</div>
			',
			'assistant_message'      => '
				<div class="ubc-llm-chat-message ubc-llm-chat-message-assistant">
					<div class="ubc-llm-chat-message-header">
						<div class="ubc-llm-chat-message-avatar">
							<span class="ubc-llm-chat-icon ubc-llm-chat-icon-assistant"></span>
						</div>
						<div class="ubc-llm-chat-message-meta">
							<span class="ubc-llm-chat-message-sender">{{model}}</span>
							<span class="ubc-llm-chat-message-time">{{time}}</span>
						</div>
					</div>
					<div class="ubc-llm-chat-message-content">{{content}}</div>
				</div>
			',
			'loading_message'        => '
				<div class="ubc-llm-chat-message ubc-llm-chat-message-assistant ubc-llm-chat-message-loading">
					<div class="ubc-llm-chat-message-header">
						<div class="ubc-llm-chat-message-avatar">
							<span class="ubc-llm-chat-icon ubc-llm-chat-icon-assistant"></span>
						</div>
						<div class="ubc-llm-chat-message-meta">
							<span class="ubc-llm-chat-message-sender">{{model}}</span>
							<span class="ubc-llm-chat-message-time">' . esc_html__( 'Thinking...', 'ubc-llm-chat' ) . '</span>
						</div>
					</div>
					<div class="ubc-llm-chat-message-content">
						<div class="ubc-llm-chat-loading-indicator">
							<span></span><span></span><span></span>
						</div>
					</div>
				</div>
			',
			'notification'           => '
				<div class="ubc-llm-chat-notification ubc-llm-chat-notification-{{type}}">
					<div class="ubc-llm-chat-notification-content">{{message}}</div>
					<button type="button" class="ubc-llm-chat-notification-close" aria-label="' . esc_attr__( 'Close', 'ubc-llm-chat' ) . '">
						<span class="ubc-llm-chat-icon ubc-llm-chat-icon-close"></span>
					</button>
				</div>
			',
			'countdown_notification' => '
				<div class="ubc-llm-chat-notification ubc-llm-chat-notification-warning ubc-llm-chat-countdown" role="alert">
					<div class="ubc-llm-chat-notification-content">
						<span class="ubc-llm-chat-notification-icon" aria-hidden="true">⏱️</span>
						<span class="ubc-llm-chat-notification-message">
							' . esc_html__( 'Rate limited. You can send another message in ', 'ubc-llm-chat' ) . '
							<span class="ubc-llm-chat-countdown-value" id="{{id}}">{{seconds}}</span>
							' . esc_html__( ' seconds.', 'ubc-llm-chat' ) . '
						</span>
					</div>
					<button type="button" class="ubc-llm-chat-notification-close" aria-label="' . esc_attr__( 'Close', 'ubc-llm-chat' ) . '">
						<span class="ubc-llm-chat-icon ubc-llm-chat-icon-close"></span>
					</button>
				</div>
			',
		);
	}
}
