<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_Claude_Provider implements AI_Provider_Interface {

	private const API_URL = 'https://api.anthropic.com/v1/messages';

	public function __construct(
		private readonly string $api_key,
		private readonly string $model = 'claude-haiku-4-5-20251001'
	) {}

	public function chat( string $system_prompt, array $messages ): array {
		$response = wp_remote_post( self::API_URL, [
			'timeout' => 60,
			'headers' => [
				'x-api-key'         => $this->api_key,
				'anthropic-version' => '2023-06-01',
				'content-type'      => 'application/json',
			],
			'body' => wp_json_encode( [
				'model'      => $this->model,
				'max_tokens' => 1024,
				// Block form so the (static) system prompt + knowledge base can be cached.
				// Prompts below the model's minimum cacheable length are simply not cached.
				'system'     => [
					[
						'type'          => 'text',
						'text'          => $system_prompt,
						'cache_control' => [ 'type' => 'ephemeral' ],
					],
				],
				'messages'   => $messages,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Claude API request failed: ' . $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			throw new RuntimeException( 'Claude API error: ' . ( $body['error']['message'] ?? 'Unknown error' ) );
		}

		$usage = $body['usage'] ?? [];

		return [
			'content' => $body['content'][0]['text'] ?? '',
			// input_tokens excludes cached tokens, so add cache writes/reads for the true total.
			'tokens'  => (int) ( $usage['input_tokens'] ?? 0 )
				+ (int) ( $usage['cache_creation_input_tokens'] ?? 0 )
				+ (int) ( $usage['cache_read_input_tokens'] ?? 0 )
				+ (int) ( $usage['output_tokens'] ?? 0 ),
		];
	}
}
