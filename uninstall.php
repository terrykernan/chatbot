<?php
// Only runs on plugin deletion, not deactivation
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Drop custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pc_messages" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pc_conversations" );

// Delete all plugin options
$options = [
	'WOO_CHATBOT_provider',
	'WOO_CHATBOT_claude_key',
	'WOO_CHATBOT_claude_model',
	'WOO_CHATBOT_openai_key',
	'WOO_CHATBOT_openai_model',
	'WOO_CHATBOT_knowledge',
	'WOO_CHATBOT_handoff_email',
	'WOO_CHATBOT_history_limit',
	'WOO_CHATBOT_widget_color',
	'WOO_CHATBOT_widget_name',
	'WOO_CHATBOT_widget_greeting',
	'WOO_CHATBOT_store_name',
	'WOO_CHATBOT_db_version',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clear any rate-limiting transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pc_rate_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_pc_rate_%'" );
