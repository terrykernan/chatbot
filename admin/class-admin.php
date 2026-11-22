<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_Admin {

	public function register_hooks(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_pc_get_transcript', [ $this, 'ajax_get_transcript' ] );
		add_action( 'wp_ajax_pc_resolve_conversation', [ $this, 'ajax_resolve_conversation' ] );
		add_action( 'wp_ajax_pc_delete_conversation', [ $this, 'ajax_delete_conversation' ] );
	}

	public function add_menu_pages(): void {
		add_menu_page(
			__( 'Woo Chatbot', 'woo-chatbot' ),
			__( 'Woo Chatbot', 'woo-chatbot' ),
			'manage_options',
			'woo-chatbot',
			[ $this, 'render_dashboard' ],
			'dashicons-format-chat',
			58
		);

		add_submenu_page( 'woo-chatbot', __( 'Dashboard', 'woo-chatbot' ), __( 'Dashboard', 'woo-chatbot' ), 'manage_options', 'woo-chatbot', [ $this, 'render_dashboard' ] );
		add_submenu_page( 'woo-chatbot', __( 'Knowledge Base', 'woo-chatbot' ), __( 'Knowledge Base', 'woo-chatbot' ), 'manage_options', 'woo-chatbot-knowledge', [ $this, 'render_knowledge' ] );
		add_submenu_page( 'woo-chatbot', __( 'Conversations', 'woo-chatbot' ), __( 'Conversations', 'woo-chatbot' ), 'manage_options', 'woo-chatbot-conversations', [ $this, 'render_conversations' ] );
		add_submenu_page( 'woo-chatbot', __( 'Settings', 'woo-chatbot' ), __( 'Settings', 'woo-chatbot' ), 'manage_options', 'woo-chatbot-settings', [ $this, 'render_settings' ] );
	}

	public function register_settings(): void {
		// --- Provider settings ---
		register_setting( 'WOO_CHATBOT_settings', 'WOO_CHATBOT_provider', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'WOO_CHATBOT_settings', 'WOO_CHATBOT_claude_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'WOO_CHATBOT_settings', 'WOO_CHATBOT_openai_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'WOO_CHATBOT_settings', 'WOO_CHATBOT_history_limit', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'WOO_CHATBOT_settings', 'WOO_CHATBOT_store_name', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Encrypted key fields - sanitised in callback
		register_setting( 'WOO_CHATBOT_settings', 'WOO_CHATBOT_claude_key', [ 'sanitize_callback' => [ $this, 'sanitize_api_key' ] ] );
		register_setting( 'WOO_CHATBOT_settings', 'WOO_CHATBOT_openai_key', [ 'sanitize_callback' => [ $this, 'sanitize_api_key' ] ] );

		// --- Widget settings ---
		register_setting( 'WOO_CHATBOT_widget', 'WOO_CHATBOT_widget_name', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'WOO_CHATBOT_widget', 'WOO_CHATBOT_widget_greeting', [ 'sanitize_callback' => 'sanitize_textarea_field' ] );
		register_setting( 'WOO_CHATBOT_widget', 'WOO_CHATBOT_widget_color', [ 'sanitize_callback' => 'sanitize_hex_color' ] );

		// --- Handoff settings ---
		register_setting( 'WOO_CHATBOT_handoff', 'WOO_CHATBOT_handoff_email', [ 'sanitize_callback' => 'sanitize_email' ] );

		// --- Knowledge ---
		register_setting( 'WOO_CHATBOT_knowledge', 'WOO_CHATBOT_knowledge', [ 'sanitize_callback' => 'wp_kses_post' ] );
	}

	public function sanitize_api_key( string $value ): string {
		// If the submitted value is a masked placeholder, keep the existing stored value
		if ( str_starts_with( $value, '••••' ) || empty( $value ) ) {
			// Determine which option we're sanitising by checking the current POST data
			if ( isset( $_POST['WOO_CHATBOT_claude_key'] ) && str_starts_with( $value, '••••' ) ) {
				return get_option( 'WOO_CHATBOT_claude_key', '' );
			}
			if ( isset( $_POST['WOO_CHATBOT_openai_key'] ) && str_starts_with( $value, '••••' ) ) {
				return get_option( 'WOO_CHATBOT_openai_key', '' );
			}
			return '';
		}
		return WOO_CHATBOT_Settings::encrypt_key( sanitize_text_field( $value ) );
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'woo-chatbot' ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_add_inline_style( 'wp-admin', '
			.pc-stat-cards { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
			.pc-stat-card { background:#fff; border:1px solid #dcdcde; border-radius:4px; padding:20px 24px; min-width:160px; }
			.pc-stat-card h3 { margin:0 0 6px; font-size:13px; color:#646970; font-weight:400; }
			.pc-stat-card .pc-stat-num { font-size:32px; font-weight:600; color:#1d2327; }
			.pc-stat-card.pc-alert .pc-stat-num { color:#d63638; }
			.pc-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:12px; font-weight:600; }
			.pc-badge-active { background:#e7f3e8; color:#1a7a2b; }
			.pc-badge-handed_off { background:#fff8e1; color:#9e6c00; }
			.pc-badge-resolved { background:#f0f0f1; color:#50575e; }
			.pc-transcript { background:#f6f7f7; border:1px solid #dcdcde; border-radius:4px; padding:12px; margin-top:8px; display:none; max-height:300px; overflow-y:auto; }
			.pc-transcript-msg { margin-bottom:8px; font-size:13px; }
			.pc-transcript-msg strong { display:inline-block; min-width:80px; }
			.pc-transcript-msg.pc-tm-user strong { color:#2271b1; }
			.pc-transcript-msg.pc-tm-assistant strong { color:#1a5c38; }
		' );

		wp_enqueue_script(
			'woo-chatbot-admin',
			WOO_CHATBOT_URL . 'admin/assets/admin.js',
			[ 'jquery', 'wp-color-picker' ],
			WOO_CHATBOT_VERSION,
			true
		);

		wp_localize_script( 'woo-chatbot-admin', 'PcAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pc_admin_nonce' ),
		] );
	}

	// -------------------------------------------------------------------------
	// Page renderers
	// -------------------------------------------------------------------------

	public function render_dashboard(): void {
		require WOO_CHATBOT_DIR . 'admin/views/page-dashboard.php';
	}

	public function render_knowledge(): void {
		require WOO_CHATBOT_DIR . 'admin/views/page-knowledge.php';
	}

	public function render_conversations(): void {
		require WOO_CHATBOT_DIR . 'admin/views/page-conversations.php';
	}

	public function render_settings(): void {
		require WOO_CHATBOT_DIR . 'admin/views/page-settings.php';
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	public function ajax_get_transcript(): void {
		check_ajax_referer( 'pc_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );

		$id       = absint( $_POST['conversation_id'] ?? 0 );
		$messages = WOO_CHATBOT_DB::get_full_transcript( $id );
		$html     = '';

		foreach ( $messages as $msg ) {
			$cls   = 'pc-transcript-msg pc-tm-' . esc_attr( $msg['role'] );
			$label = $msg['role'] === 'user' ? 'Visitor' : 'Bot';
			$html .= '<div class="' . $cls . '"><strong>' . esc_html( $label ) . ':</strong> ' . nl2br( esc_html( $msg['content'] ) ) . '</div>';
		}

		wp_send_json_success( [ 'html' => $html ] );
	}

	public function ajax_resolve_conversation(): void {
		check_ajax_referer( 'pc_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );

		$id = absint( $_POST['conversation_id'] ?? 0 );
		WOO_CHATBOT_DB::mark_resolved( $id );
		wp_send_json_success();
	}

	public function ajax_delete_conversation(): void {
		check_ajax_referer( 'pc_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );

		$id = absint( $_POST['conversation_id'] ?? 0 );
		WOO_CHATBOT_DB::delete_conversation( $id );
		wp_send_json_success();
	}
}
