<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_Activator {

	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
		flush_rewrite_rules();
	}

	private static function create_tables(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$conversations = "CREATE TABLE {$wpdb->prefix}pc_conversations (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(64) NOT NULL,
			ip_address VARCHAR(45) DEFAULT '',
			user_agent TEXT DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			handoff_requested_at DATETIME NULL DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY status (status)
		) $charset;";

		$messages = "CREATE TABLE {$wpdb->prefix}pc_messages (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id BIGINT UNSIGNED NOT NULL,
			role VARCHAR(10) NOT NULL,
			content LONGTEXT NOT NULL,
			provider VARCHAR(50) DEFAULT '',
			tokens_used INT NULL DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY conversation_id (conversation_id)
		) $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $conversations );
		dbDelta( $messages );

		update_option( 'WOO_CHATBOT_db_version', WOO_CHATBOT_VERSION );
	}

	private static function set_default_options(): void {
		$defaults = [
			'WOO_CHATBOT_provider'        => 'claude',
			'WOO_CHATBOT_claude_key'      => '',
			'WOO_CHATBOT_claude_model'    => 'claude-haiku-4-5-20251001',
			'WOO_CHATBOT_openai_key'      => '',
			'WOO_CHATBOT_openai_model'    => 'gpt-4o-mini',
			'WOO_CHATBOT_handoff_email'   => get_option( 'admin_email' ),
			'WOO_CHATBOT_history_limit'   => 10,
			'WOO_CHATBOT_widget_color'    => '#1a5c38',
			'WOO_CHATBOT_widget_name'     => 'Woo Chatbot Assistant',
			'WOO_CHATBOT_widget_greeting' => 'Hi! I can help with paint choices, coverage calculations, and delivery info. What do you need?',
			'WOO_CHATBOT_store_name'      => get_option( 'blogname' ),
		];

		foreach ( $defaults as $key => $value ) {
			if ( get_option( $key ) === false ) {
				update_option( $key, $value );
			}
		}

		self::seed_knowledge_if_empty();
	}

	/**
	 * Seed the knowledge option from knowledge.md when it is missing or empty.
	 * Returns true when the option was (re)written.
	 */
	public static function seed_knowledge_if_empty(): bool {
		$current = get_option( 'WOO_CHATBOT_knowledge', '' );
		if ( is_string( $current ) && trim( $current ) !== '' ) {
			return false;
		}
		$knowledge = self::default_knowledge();
		if ( $knowledge === '' ) {
			return false;
		}
		return update_option( 'WOO_CHATBOT_knowledge', $knowledge );
	}

	/**
	 * Contents of the bundled knowledge.md (the source of truth for the default knowledge base).
	 */
	public static function default_knowledge(): string {
		$file = plugin_dir_path( __DIR__ ) . 'knowledge.md';
		if ( ! is_readable( $file ) ) {
			return '';
		}
		return trim( (string) file_get_contents( $file ) );
	}
}
