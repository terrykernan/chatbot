<?php
defined( 'ABSPATH' ) || exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Insufficient permissions.', 'woo-chatbot' ) );

$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$current_page  = max( 1, absint( $_GET['paged'] ?? 1 ) );
$per_page      = 20;
$conversations = WOO_CHATBOT_DB::get_conversations( $status_filter, $per_page, $current_page );
$total         = WOO_CHATBOT_DB::count_conversations( $status_filter );
$total_pages   = ceil( $total / $per_page );

$statuses = [
	''           => __( 'All', 'woo-chatbot' ),
	'active'     => __( 'Active', 'woo-chatbot' ),
	'handed_off' => __( 'Handed Off', 'woo-chatbot' ),
	'resolved'   => __( 'Resolved', 'woo-chatbot' ),
];
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Conversations', 'woo-chatbot' ); ?></h1>

	<ul class="subsubsub">
		<?php foreach ( $statuses as $slug => $label ) :
			$count = WOO_CHATBOT_DB::count_conversations( $slug );
			$url   = admin_url( 'admin.php?page=woo-chatbot-conversations' . ( $slug ? '&status=' . $slug : '' ) );
			$class = $status_filter === $slug ? 'current' : '';
		?>
			<li><a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?> <span class="count">(<?php echo esc_html( $count ); ?>)</span></a> | </li>
		<?php endforeach; ?>
	</ul>
	<br class="clear">

	<?php if ( empty( $conversations ) ) : ?>
		<p><?php esc_html_e( 'No conversations found.', 'woo-chatbot' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped" style="margin-top:8px;">
			<thead>
				<tr>
					<th style="width:160px;"><?php esc_html_e( 'Date', 'woo-chatbot' ); ?></th>
					<th style="width:120px;"><?php esc_html_e( 'Session', 'woo-chatbot' ); ?></th>
					<th style="width:120px;"><?php esc_html_e( 'IP Address', 'woo-chatbot' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'Status', 'woo-chatbot' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'woo-chatbot' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $conversations as $conv ) :
					$id = (int) $conv['id'];
				?>
				<tr>
					<td><?php echo esc_html( $conv['created_at'] ); ?></td>
					<td><code><?php echo esc_html( substr( $conv['session_id'], 0, 8 ) ); ?>…</code></td>
					<td><?php echo esc_html( $conv['ip_address'] ); ?></td>
					<td><span class="pc-badge pc-badge-<?php echo esc_attr( $conv['status'] ); ?> pc-status-badge"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $conv['status'] ) ) ); ?></span></td>
					<td>
						<button class="button button-small pc-toggle-transcript" data-id="<?php echo esc_attr( $id ); ?>">View</button>
						<?php if ( $conv['status'] === 'handed_off' ) : ?>
							<button class="button button-small pc-resolve-btn" data-id="<?php echo esc_attr( $id ); ?>">Resolve</button>
						<?php endif; ?>
						<button class="button button-small pc-delete-btn" data-id="<?php echo esc_attr( $id ); ?>">Delete</button>
					</td>
				</tr>
				<tr class="pc-transcript-row">
					<td colspan="5" style="padding:0 12px 8px;">
						<div class="pc-transcript" id="pc-transcript-<?php echo esc_attr( $id ); ?>"></div>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) :
			$base = admin_url( 'admin.php?page=woo-chatbot-conversations' . ( $status_filter ? '&status=' . $status_filter : '' ) . '&paged=%#%' );
			echo wp_kses_post( paginate_links( [
				'base'      => $base,
				'format'    => '',
				'current'   => $current_page,
				'total'     => $total_pages,
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
			] ) );
		endif; ?>
	<?php endif; ?>
</div>
