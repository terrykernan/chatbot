<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT {

	public function run(): void {
		$this->load_dependencies();

		if ( is_admin() ) {
			( new WOO_CHATBOT_Admin() )->register_hooks();
		}

		( new WOO_CHATBOT_Public() )->register_hooks();
	}

	private function load_dependencies(): void {
		require_once WOO_CHATBOT_DIR . 'includes/class-database.php';
		require_once WOO_CHATBOT_DIR . 'includes/class-settings.php';
		require_once WOO_CHATBOT_DIR . 'includes/class-mailer.php';
		require_once WOO_CHATBOT_DIR . 'includes/providers/interface-ai-provider.php';
		require_once WOO_CHATBOT_DIR . 'includes/providers/class-claude-provider.php';
		require_once WOO_CHATBOT_DIR . 'includes/providers/class-openai-provider.php';
		require_once WOO_CHATBOT_DIR . 'admin/class-admin.php';
		require_once WOO_CHATBOT_DIR . 'public/class-public.php';
	}
}
