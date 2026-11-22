<?php
defined( 'ABSPATH' ) || exit;

interface AI_Provider_Interface {
	/**
	 * Send a chat request to the AI provider.
	 *
	 * @param string $system_prompt The system prompt.
	 * @param array  $messages      Array of ['role' => 'user'|'assistant', 'content' => string].
	 *
	 * @return array{content: string, tokens: int}
	 * @throws RuntimeException On API error.
	 */
	public function chat( string $system_prompt, array $messages ): array;
}
