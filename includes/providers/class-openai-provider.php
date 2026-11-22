<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_OpenAI_Provider implements AI_Provider_Interface {

	private const API_URL = 'https://api.openai.com/v1/chat/completions';

	public function __construct(
		private readonly string $api_key,
		private readonly string $model = 'gpt-4o-mini'
	) {}

	public function chat( string $system_prompt, array $messages ): array {
		$payload = array_merge(
			[ [ 'role' => 'system', 'content' => $system_prompt ] ],
			$messages
		);

		$response = wp_remote_post( self::API_URL, [
			'timeout' => 60,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( [
				'model'      => $this->model,
				'max_tokens' => 1024,
				'messages'   => $payload,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'OpenAI API request failed: ' . $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			throw new RuntimeException( 'OpenAI API error: ' . ( $body['error']['message'] ?? 'Unknown error' ) );
		}

		return [
			'content' => $body['choices'][0]['message']['content'] ?? '',
			'tokens'  => $body['usage']['total_tokens'] ?? 0,
		];
	}
}
