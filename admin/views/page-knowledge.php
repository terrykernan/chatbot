<?php
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Insufficient permissions.', 'woo-chatbot' ) );

if ( isset( $_POST['pc_import_knowledge_file'] ) ) {
	check_admin_referer( 'pc_import_knowledge' );
	$from_file = WOO_CHATBOT_Activator::default_knowledge();
	if ( $from_file === '' ) {
		add_settings_error( 'WOO_CHATBOT_knowledge', 'missing', __( 'knowledge.md was not found or is empty.', 'woo-chatbot' ), 'error' );
	} else {
		update_option( 'WOO_CHATBOT_knowledge', $from_file );
		add_settings_error( 'WOO_CHATBOT_knowledge', 'imported', __( 'Knowledge base replaced with the contents of knowledge.md.', 'woo-chatbot' ), 'success' );
	}
} elseif ( isset( $_POST['WOO_CHATBOT_knowledge'] ) ) {
	check_admin_referer( 'pc_save_knowledge' );
	update_option( 'WOO_CHATBOT_knowledge', wp_kses_post( wp_unslash( $_POST['WOO_CHATBOT_knowledge'] ) ) );
	add_settings_error( 'WOO_CHATBOT_knowledge', 'saved', __( 'Knowledge base updated.', 'woo-chatbot' ), 'success' );
}

settings_errors( 'WOO_CHATBOT_knowledge' );
$knowledge = WOO_CHATBOT_Settings::get_knowledge();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Knowledge Base', 'woo-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'This content is injected into every chat conversation. Use Markdown formatting. The chatbot will only answer from this content.', 'woo-chatbot' ); ?></p>

	<form method="post">
		<?php wp_nonce_field( 'pc_save_knowledge' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="WOO_CHATBOT_knowledge"><?php esc_html_e( 'Knowledge Document', 'woo-chatbot' ); ?></label></th>
				<td>
					<textarea
						name="WOO_CHATBOT_knowledge"
						id="WOO_CHATBOT_knowledge"
						rows="30"
						class="large-text code"
						style="font-family:monospace;font-size:13px;"
					><?php echo esc_textarea( $knowledge ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Supports Markdown: ## Headings, **bold**, - bullet lists.', 'woo-chatbot' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save Knowledge Base', 'woo-chatbot' ) ); ?>
	</form>

	<hr>
	<h2><?php esc_html_e( 'Reload from file', 'woo-chatbot' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Overwrite the knowledge base above with the knowledge.md file bundled in the plugin folder. Any edits made here that are not in that file will be lost.', 'woo-chatbot' ); ?></p>
	<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Replace the current knowledge base with knowledge.md?', 'woo-chatbot' ) ); ?>');">
		<?php wp_nonce_field( 'pc_import_knowledge' ); ?>
		<?php submit_button( __( 'Replace with knowledge.md', 'woo-chatbot' ), 'secondary', 'pc_import_knowledge_file', false ); ?>
	</form>
</div>
