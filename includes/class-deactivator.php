<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_Deactivator {
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
