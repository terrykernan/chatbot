<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_DB {

	// -------------------------------------------------------------------------
	// Conversations
	// -------------------------------------------------------------------------

	public static function get_or_create_conversation( string $session_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'pc_conversations';

		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM $table WHERE session_id = %s LIMIT 1", $session_id )
		);

		if ( $existing ) {
			return (int) $existing;
		}

		$wpdb->insert( $table, [
			'session_id' => $session_id,
			'ip_address' => self::get_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'status'     => 'active',
			'created_at' => current_time( 'mysql' ),
		] );

		return (int) $wpdb->insert_id;
	}

	public static function mark_handed_off( int $conversation_id ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'pc_conversations',
			[ 'status' => 'handed_off', 'handoff_requested_at' => current_time( 'mysql' ) ],
			[ 'id' => $conversation_id ]
		);
	}

	public static function mark_resolved( int $conversation_id ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'pc_conversations',
			[ 'status' => 'resolved' ],
			[ 'id' => $conversation_id ]
		);
	}

	public static function get_conversations( string $status = '', int $per_page = 20, int $page = 1 ): array {
		global $wpdb;
		$table  = $wpdb->prefix . 'pc_conversations';
		$offset = ( $page - 1 ) * $per_page;
		$where  = $status ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';

		return $wpdb->get_results(
			"SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset",
			ARRAY_A
		);
	}

	public static function count_conversations( string $status = '' ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'pc_conversations';
		$where = $status ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );
	}

	public static function delete_conversation( int $conversation_id ): void {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'pc_messages', [ 'conversation_id' => $conversation_id ] );
		$wpdb->delete( $wpdb->prefix . 'pc_conversations', [ 'id' => $conversation_id ] );
	}

	// -------------------------------------------------------------------------
	// Messages
	// -------------------------------------------------------------------------

	public static function save_message( int $conversation_id, string $role, string $content, string $provider = '', ?int $tokens = null ): void {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'pc_messages', [
			'conversation_id' => $conversation_id,
			'role'            => $role,
			'content'         => $content,
			'provider'        => $provider,
			'tokens_used'     => $tokens,
			'created_at'      => current_time( 'mysql' ),
		] );
	}

	public static function get_messages( int $conversation_id, int $limit = 0 ): array {
		global $wpdb;
		$table      = $wpdb->prefix . 'pc_messages';
		$limit_sql  = $limit ? "LIMIT $limit" : '';

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT role, content FROM $table WHERE conversation_id = %d ORDER BY created_at ASC $limit_sql", $conversation_id ),
			ARRAY_A
		);

		return $rows ?: [];
	}

	public static function get_full_transcript( int $conversation_id ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT role, content, provider, tokens_used, created_at FROM {$wpdb->prefix}pc_messages WHERE conversation_id = %d ORDER BY created_at ASC",
				$conversation_id
			),
			ARRAY_A
		) ?: [];
	}

	public static function count_messages_today(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}pc_messages WHERE DATE(created_at) = CURDATE()"
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private static function get_ip(): string {
		foreach ( [ 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );
				return trim( $ip[0] );
			}
		}
		return '';
	}
}
