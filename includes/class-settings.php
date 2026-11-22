<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_Settings {

	public static function get( string $key, mixed $default = '' ): mixed {
		return get_option( $key, $default );
	}

	public static function get_provider(): AI_Provider_Interface {
		$provider = self::get( 'WOO_CHATBOT_provider', 'claude' );

		if ( $provider === 'openai' ) {
			return new WOO_CHATBOT_OpenAI_Provider(
				self::decrypt_key( self::get( 'WOO_CHATBOT_openai_key' ) ),
				self::get( 'WOO_CHATBOT_openai_model', 'gpt-4o-mini' )
			);
		}

		return new WOO_CHATBOT_Claude_Provider(
			self::decrypt_key( self::get( 'WOO_CHATBOT_claude_key' ) ),
			self::get( 'WOO_CHATBOT_claude_model', 'claude-haiku-4-5-20251001' )
		);
	}

	/**
	 * Knowledge base text; seeds it from knowledge.md first if the stored copy is empty.
	 */
	public static function get_knowledge(): string {
		WOO_CHATBOT_Activator::seed_knowledge_if_empty();
		return (string) self::get( 'WOO_CHATBOT_knowledge', '' );
	}

	public static function get_system_prompt(): string {
		$name      = self::get( 'WOO_CHATBOT_widget_name', 'Woo Chatbot Assistant' );
		$store     = self::get( 'WOO_CHATBOT_store_name', get_option( 'blogname' ) );
		$knowledge = self::get_knowledge();

		return "You are {$name}, a helpful assistant for {$store}, a paint retailer.

RULES:
- Only answer questions using the KNOWLEDGE BASE provided below.
- Do not invent prices, delivery times, or product names.
- If the answer is not covered in the knowledge base, say so honestly and offer to connect the visitor with the team.
- If the visitor asks to speak to a human, or requests human help, reply with exactly: [HANDOFF_REQUESTED]
- Keep answers concise, friendly, and practical.

--- KNOWLEDGE BASE ---
{$knowledge}";
	}

	public static function encrypt_key( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$key    = self::encryption_key();
		$iv     = openssl_random_pseudo_bytes( 16 );
		$cipher = openssl_encrypt( $value, 'AES-256-CBC', $key, 0, $iv );
		return base64_encode( $iv . $cipher );
	}

	public static function decrypt_key( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$key    = self::encryption_key();
		$data   = base64_decode( $value );
		$iv     = substr( $data, 0, 16 );
		$cipher = substr( $data, 16 );
		return openssl_decrypt( $cipher, 'AES-256-CBC', $key, 0, $iv ) ?: '';
	}

	private static function encryption_key(): string {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'woo-chatbot-fallback-key';
		return hash( 'sha256', $salt . 'WOO_CHATBOT', true );
	}
}
