<?php
/**
 * Admin header template.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Admin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get current tab.
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'llm_services';
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="?page=ubc-llm-chat&tab=llm_services" class="nav-tab <?php echo 'llm_services' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'LLM Services', 'ubc-llm-chat' ); ?>
		</a>
		<a href="?page=ubc-llm-chat&tab=global_settings" class="nav-tab <?php echo 'global_settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Global Settings', 'ubc-llm-chat' ); ?>
		</a>
		<a href="?page=ubc-llm-chat&tab=usage_tracking" class="nav-tab <?php echo 'usage_tracking' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Usage Tracking', 'ubc-llm-chat' ); ?>
		</a>
	</h2>

	<form method="post" action="options.php">
