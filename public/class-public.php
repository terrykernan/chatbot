<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_Public {

	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_widget' ] );
	}

	public function register_rest_routes(): void {
		register_rest_route( 'woo-chatbot/v1', '/chat', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_chat' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'session_id' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
				'message'    => [ 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ],
			],
		] );
	}

	public function handle_chat( WP_REST_Request $request ): WP_REST_Response {
		// Nonce check
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_REST_Response( [ 'error' => 'Invalid nonce.' ], 403 );
		}

		// Rate limit: 30 requests per 10 minutes per IP
		if ( ! $this->check_rate_limit() ) {
			return new WP_REST_Response( [ 'error' => 'Too many requests. Please wait a moment.' ], 429 );
		}

		$session_id = $request->get_param( 'session_id' );
		$message    = $request->get_param( 'message' );

		if ( empty( $session_id ) || empty( $message ) ) {
			return new WP_REST_Response( [ 'error' => 'Missing required fields.' ], 400 );
		}

		// Validate session_id looks like a UUID
		if ( ! preg_match( '/^[0-9a-f\-]{36}$/', $session_id ) ) {
			return new WP_REST_Response( [ 'error' => 'Invalid session.' ], 400 );
		}

		try {
			$conversation_id = WOO_CHATBOT_DB::get_or_create_conversation( $session_id );
			$limit           = (int) WOO_CHATBOT_Settings::get( 'WOO_CHATBOT_history_limit', 10 );
			$history         = WOO_CHATBOT_DB::get_messages( $conversation_id );

			// Build messages array for provider (last $limit * 2 messages, preserving pairs)
			$context = array_slice( $history, -( $limit * 2 ) );
			$context[] = [ 'role' => 'user', 'content' => $message ];

			$system_prompt = WOO_CHATBOT_Settings::get_system_prompt();
			$provider_name = WOO_CHATBOT_Settings::get( 'WOO_CHATBOT_provider', 'claude' );
			$provider      = WOO_CHATBOT_Settings::get_provider();

			$result  = $provider->chat( $system_prompt, $context );
			$content = $result['content'];
			$tokens  = $result['tokens'];

			$handoff = str_contains( $content, '[HANDOFF_REQUESTED]' );
			$content = trim( str_replace( '[HANDOFF_REQUESTED]', '', $content ) );

			if ( empty( $content ) && $handoff ) {
				$content = 'Of course! A member of our team will be in touch with you shortly.';
			}

			// Save messages
			WOO_CHATBOT_DB::save_message( $conversation_id, 'user', $message, $provider_name );
			WOO_CHATBOT_DB::save_message( $conversation_id, 'assistant', $content, $provider_name, $tokens );

			if ( $handoff ) {
				WOO_CHATBOT_DB::mark_handed_off( $conversation_id );
				WOO_CHATBOT_Mailer::send_handoff( $conversation_id );
			}

			return new WP_REST_Response( [
				'message' => $content,
				'handoff' => $handoff,
			], 200 );

		} catch ( RuntimeException $e ) {
			error_log( 'Woo Chatbot error: ' . $e->getMessage() );
			return new WP_REST_Response( [
				'error' => 'Sorry, I\'m having trouble connecting right now. Please try again shortly.',
			], 500 );
		}
	}

	public function enqueue_widget(): void {
		wp_enqueue_script(
			'woo-chatbot-widget',
			WOO_CHATBOT_URL . 'public/assets/chatbot.js',
			[],
			WOO_CHATBOT_VERSION,
			true
		);

		wp_localize_script( 'woo-chatbot-widget', 'WooChatbotConfig', [
			'restUrl'  => esc_url_raw( rest_url( 'woo-chatbot/v1/chat' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'botName'  => esc_js( WOO_CHATBOT_Settings::get( 'WOO_CHATBOT_widget_name', 'Woo Chatbot Assistant' ) ),
			'greeting' => esc_js( WOO_CHATBOT_Settings::get( 'WOO_CHATBOT_widget_greeting', 'Hi! How can I help?' ) ),
			'color'    => esc_js( WOO_CHATBOT_Settings::get( 'WOO_CHATBOT_widget_color', '#1a5c38' ) ),
		] );
	}

	private function check_rate_limit(): bool {
		$ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$key = 'pc_rate_' . md5( $ip );
		$hits = (int) get_transient( $key );

		if ( $hits >= 30 ) {
			return false;
		}

		set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );
		return true;
	}
}
