<?php
/**
 * FreeCRM - Mail module tunables.
 */

$CONFIG = [
	'default_scan_interval' => 120,
	'max_consecutive_failures' => 5,
	'attachment_max_size_mb' => 25,
	'purge_info_logs_days' => 30,
	'purge_error_logs_days' => 180,
	'compose_upload_ttl_minutes' => 60,
	'send_rate_limit_per_minute' => 60,
	'password_mask' => '**********',

	'MAILER_REQUIRED_ACCEPTATION_BEFORE_SENDING' => false,
	'MAIL_FILTER_SEND_ONLY_TO_DOMAIN' => '',
	'MAIL_AUDIT_LOG_ENABLED' => false,
	'AUDIT_LOG_RETENTION_DAYS' => 365,
	'DELAYED_EMAIL_BUFFER_ENABLED' => false,
	'DELAYED_EMAIL_DEFAULT_MINUTES' => 120,

	// Org-wide defaults for new personal mail accounts (hosts/ports only; username/password/from_name are per user).
	'personal_account_defaults' => [
		'imap_host' => 'itconnect.pl',
		'imap_port' => 993,
		'imap_secure' => 'ssl',
		'imap_validate_cert' => 1,
		'imap_folder_inbox' => 'INBOX',
		'imap_folder_sent' => 'Sent',
		'smtp_host' => 'itconnect.pl',
		'smtp_port' => 465,
		'smtp_secure' => 'ssl',
		'append_sent' => 1,
		'reply_to_mode' => 'same_as_from',
	],
];
