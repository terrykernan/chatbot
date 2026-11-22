<?php
defined( 'ABSPATH' ) || exit;

class WOO_CHATBOT_Mailer {

	public static function send_handoff( int $conversation_id ): void {
		$to      = WOO_CHATBOT_Settings::get( 'WOO_CHATBOT_handoff_email', get_option( 'admin_email' ) );
		$subject = '[Woo Chatbot] Customer requesting assistance';
		$body    = self::build_body( $conversation_id );
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		wp_mail( $to, $subject, $body, $headers );
	}

	private static function build_body( int $conversation_id ): string {
		$messages  = WOO_CHATBOT_DB::get_full_transcript( $conversation_id );
		$admin_url = admin_url( 'admin.php?page=woo-chatbot-conversations&conversation=' . $conversation_id );
		$store     = esc_html( get_option( 'blogname' ) );

		$html  = "<p>A visitor on <strong>{$store}</strong> has requested to speak with a member of the team.</p>";
		$html .= '<hr><h3>Conversation Transcript</h3>';
		$html .= '<table cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;">';

		foreach ( $messages as $msg ) {
			$bg    = $msg['role'] === 'user' ? '#f0f4ff' : '#f9f9f9';
			$label = $msg['role'] === 'user' ? 'Visitor' : 'Bot';
			$html .= "<tr style='background:{$bg};'><td style='width:60px;font-weight:bold;vertical-align:top;padding:8px;'>{$label}</td>";
			$html .= '<td style="padding:8px;">' . nl2br( esc_html( $msg['content'] ) ) . '</td></tr>';
		}

		$html .= '</table>';
		$html .= "<p><a href='{$admin_url}'>View conversation in WordPress admin</a></p>";

		return $html;
	}
}
