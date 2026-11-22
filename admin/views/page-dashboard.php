<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Woo Chatbot', 'woo-chatbot' ); ?></h1>

	<?php
	$total        = WOO_CHATBOT_DB::count_conversations();
	$handed_off   = WOO_CHATBOT_DB::count_conversations( 'handed_off' );
	$active       = WOO_CHATBOT_DB::count_conversations( 'active' );
	$msgs_today   = WOO_CHATBOT_DB::count_messages_today();
	$recent       = WOO_CHATBOT_DB::get_conversations( '', 10 );
	?>

	<div class="pc-stat-cards">
		<div class="pc-stat-card">
			<h3><?php esc_html_e( 'Total Conversations', 'woo-chatbot' ); ?></h3>
			<div class="pc-stat-num"><?php echo esc_html( $total ); ?></div>
		</div>
		<div class="pc-stat-card">
			<h3><?php esc_html_e( 'Active', 'woo-chatbot' ); ?></h3>
			<div class="pc-stat-num"><?php echo esc_html( $active ); ?></div>
		</div>
		<div class="pc-stat-card<?php echo $handed_off ? ' pc-alert' : ''; ?>">
			<h3><?php esc_html_e( 'Awaiting Handoff', 'woo-chatbot' ); ?></h3>
			<div class="pc-stat-num"><?php echo esc_html( $handed_off ); ?></div>
		</div>
		<div class="pc-stat-card">
			<h3><?php esc_html_e( 'Messages Today', 'woo-chatbot' ); ?></h3>
			<div class="pc-stat-num"><?php echo esc_html( $msgs_today ); ?></div>
		</div>
	</div>

	<h2><?php esc_html_e( 'Recent Conversations', 'woo-chatbot' ); ?></h2>

	<?php if ( empty( $recent ) ) : ?>
		<p><?php esc_html_e( 'No conversations yet.', 'woo-chatbot' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'woo-chatbot' ); ?></th>
					<th><?php esc_html_e( 'Session', 'woo-chatbot' ); ?></th>
					<th><?php esc_html_e( 'IP', 'woo-chatbot' ); ?></th>
					<th><?php esc_html_e( 'Status', 'woo-chatbot' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'woo-chatbot' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent as $conv ) : ?>
				<tr>
					<td><?php echo esc_html( $conv['created_at'] ); ?></td>
					<td><code><?php echo esc_html( substr( $conv['session_id'], 0, 8 ) ); ?>…</code></td>
					<td><?php echo esc_html( $conv['ip_address'] ); ?></td>
					<td><span class="pc-badge pc-badge-<?php echo esc_attr( $conv['status'] ); ?> pc-status-badge"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $conv['status'] ) ) ); ?></span></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-chatbot-conversations&view=' . $conv['id'] ) ); ?>"><?php esc_html_e( 'View', 'woo-chatbot' ); ?></a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-chatbot-conversations' ) ); ?>"><?php esc_html_e( 'View all conversations →', 'woo-chatbot' ); ?></a></p>
	<?php endif; ?>
</div>
