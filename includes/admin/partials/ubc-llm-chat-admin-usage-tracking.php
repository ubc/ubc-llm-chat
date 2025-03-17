<?php
/**
 * Admin Usage Tracking tab content.
 *
 * @since      1.0.0
 * @package    UBC\LLMChat\Admin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get usage statistics.
$users_with_conversations = get_users(
	array(
		'meta_key'     => 'ubc_llm_chat_conversation_count',
		'meta_compare' => 'EXISTS',
	)
);

$total_conversations = 0;
$total_messages      = 0;

foreach ( $users_with_conversations as $user ) {
	$conversation_count = get_user_meta( $user->ID, 'ubc_llm_chat_conversation_count', true );
	$message_count      = get_user_meta( $user->ID, 'ubc_llm_chat_message_count', true );

	$total_conversations += intval( $conversation_count );
	$total_messages      += intval( $message_count );
}
?>

<div class="ubc-llm-chat-usage-stats">
	<h3><?php esc_html_e( 'Usage Statistics', 'ubc-llm-chat' ); ?></h3>

	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Metric', 'ubc-llm-chat' ); ?></th>
				<th><?php esc_html_e( 'Value', 'ubc-llm-chat' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Total Users with Conversations', 'ubc-llm-chat' ); ?></td>
				<td><?php echo esc_html( count( $users_with_conversations ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Total Conversations', 'ubc-llm-chat' ); ?></td>
				<td><?php echo esc_html( $total_conversations ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Total Messages', 'ubc-llm-chat' ); ?></td>
				<td><?php echo esc_html( $total_messages ); ?></td>
			</tr>
		</tbody>
	</table>
</div>
