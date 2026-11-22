<?php
defined( 'ABSPATH' ) || exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Insufficient permissions.', 'woo-chatbot' ) );

// Determine which tab is active
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'provider';
$tabs = [
	'provider' => __( 'AI Provider', 'woo-chatbot' ),
	'widget'   => __( 'Widget', 'woo-chatbot' ),
	'handoff'  => __( 'Handoff', 'woo-chatbot' ),
];

// Mask stored API keys for display
function pc_mask_key( string $option ): string {
	$raw = get_option( $option, '' );
	if ( empty( $raw ) ) return '';
	$decrypted = WOO_CHATBOT_Settings::decrypt_key( $raw );
	return empty( $decrypted ) ? '' : '••••••••' . substr( $decrypted, -4 );
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Woo Chatbot — Settings', 'woo-chatbot' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $slug => $label ) :
			$url   = admin_url( 'admin.php?page=woo-chatbot-settings&tab=' . $slug );
			$class = $active_tab === $slug ? 'nav-tab nav-tab-active' : 'nav-tab';
		?>
			<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php settings_errors(); ?>

	<form method="post" action="options.php" style="margin-top:12px;">

		<?php if ( $active_tab === 'provider' ) :
			settings_fields( 'WOO_CHATBOT_settings' );
			$provider = get_option( 'WOO_CHATBOT_provider', 'claude' );
		?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="WOO_CHATBOT_store_name"><?php esc_html_e( 'Store Name', 'woo-chatbot' ); ?></label></th>
					<td><input type="text" name="WOO_CHATBOT_store_name" id="WOO_CHATBOT_store_name" value="<?php echo esc_attr( get_option( 'WOO_CHATBOT_store_name', get_option( 'blogname' ) ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="WOO_CHATBOT_provider"><?php esc_html_e( 'AI Provider', 'woo-chatbot' ); ?></label></th>
					<td>
						<select name="WOO_CHATBOT_provider" id="WOO_CHATBOT_provider">
							<option value="claude" <?php selected( $provider, 'claude' ); ?>>Claude (Anthropic)</option>
							<option value="openai" <?php selected( $provider, 'openai' ); ?>>OpenAI</option>
						</select>
					</td>
				</tr>
			</table>

			<div class="pc-provider-fields" data-provider="claude">
				<h3><?php esc_html_e( 'Claude Settings', 'woo-chatbot' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="WOO_CHATBOT_claude_key"><?php esc_html_e( 'Claude API Key', 'woo-chatbot' ); ?></label></th>
						<td>
							<input type="password" name="WOO_CHATBOT_claude_key" id="WOO_CHATBOT_claude_key" value="<?php echo esc_attr( pc_mask_key( 'WOO_CHATBOT_claude_key' ) ); ?>" class="regular-text" autocomplete="off">
							<p class="description"><?php esc_html_e( 'Leave unchanged to keep existing key.', 'woo-chatbot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="WOO_CHATBOT_claude_model"><?php esc_html_e( 'Model', 'woo-chatbot' ); ?></label></th>
						<td>
							<select name="WOO_CHATBOT_claude_model" id="WOO_CHATBOT_claude_model">
								<?php
								$claude_models = [
									'claude-haiku-4-5-20251001' => 'Claude Haiku (Fastest / Cheapest)',
									'claude-sonnet-4-6'         => 'Claude Sonnet (Balanced)',
								];
								$current = get_option( 'WOO_CHATBOT_claude_model', 'claude-haiku-4-5-20251001' );
								foreach ( $claude_models as $val => $label ) :
								?>
									<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<div class="pc-provider-fields" data-provider="openai">
				<h3><?php esc_html_e( 'OpenAI Settings', 'woo-chatbot' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="WOO_CHATBOT_openai_key"><?php esc_html_e( 'OpenAI API Key', 'woo-chatbot' ); ?></label></th>
						<td>
							<input type="password" name="WOO_CHATBOT_openai_key" id="WOO_CHATBOT_openai_key" value="<?php echo esc_attr( pc_mask_key( 'WOO_CHATBOT_openai_key' ) ); ?>" class="regular-text" autocomplete="off">
							<p class="description"><?php esc_html_e( 'Leave unchanged to keep existing key.', 'woo-chatbot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="WOO_CHATBOT_openai_model"><?php esc_html_e( 'Model', 'woo-chatbot' ); ?></label></th>
						<td>
							<select name="WOO_CHATBOT_openai_model" id="WOO_CHATBOT_openai_model">
								<?php
								$openai_models = [
									'gpt-4o-mini' => 'GPT-4o Mini (Cheapest)',
									'gpt-4o'      => 'GPT-4o (Balanced)',
								];
								$current = get_option( 'WOO_CHATBOT_openai_model', 'gpt-4o-mini' );
								foreach ( $openai_models as $val => $label ) :
								?>
									<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="WOO_CHATBOT_history_limit"><?php esc_html_e( 'Conversation History Limit', 'woo-chatbot' ); ?></label></th>
					<td>
						<input type="number" name="WOO_CHATBOT_history_limit" id="WOO_CHATBOT_history_limit" value="<?php echo esc_attr( get_option( 'WOO_CHATBOT_history_limit', 10 ) ); ?>" min="1" max="50" class="small-text">
						<p class="description"><?php esc_html_e( 'Number of prior message pairs to include per request. Lower = cheaper.', 'woo-chatbot' ); ?></p>
					</td>
				</tr>
			</table>

		<?php elseif ( $active_tab === 'widget' ) :
			settings_fields( 'WOO_CHATBOT_widget' );
		?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="WOO_CHATBOT_widget_name"><?php esc_html_e( 'Bot Name', 'woo-chatbot' ); ?></label></th>
					<td><input type="text" name="WOO_CHATBOT_widget_name" id="WOO_CHATBOT_widget_name" value="<?php echo esc_attr( get_option( 'WOO_CHATBOT_widget_name', 'Woo Chatbot Assistant' ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="WOO_CHATBOT_widget_greeting"><?php esc_html_e( 'Greeting Message', 'woo-chatbot' ); ?></label></th>
					<td><textarea name="WOO_CHATBOT_widget_greeting" id="WOO_CHATBOT_widget_greeting" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'WOO_CHATBOT_widget_greeting', 'Hi! How can I help?' ) ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="WOO_CHATBOT_widget_color"><?php esc_html_e( 'Widget Colour', 'woo-chatbot' ); ?></label></th>
					<td><input type="text" name="WOO_CHATBOT_widget_color" id="WOO_CHATBOT_widget_color" value="<?php echo esc_attr( get_option( 'WOO_CHATBOT_widget_color', '#1a5c38' ) ); ?>" class="pc-color-picker"></td>
				</tr>
			</table>

		<?php elseif ( $active_tab === 'handoff' ) :
			settings_fields( 'WOO_CHATBOT_handoff' );
		?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="WOO_CHATBOT_handoff_email"><?php esc_html_e( 'Handoff Email Address', 'woo-chatbot' ); ?></label></th>
					<td>
						<input type="email" name="WOO_CHATBOT_handoff_email" id="WOO_CHATBOT_handoff_email" value="<?php echo esc_attr( get_option( 'WOO_CHATBOT_handoff_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'When a visitor requests to speak with a human, the full transcript is emailed here.', 'woo-chatbot' ); ?></p>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<?php submit_button(); ?>
	</form>
</div>
