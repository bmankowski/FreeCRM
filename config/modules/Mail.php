<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
$CONFIG = [
	// URL address characters limit for mailto links, 
	/*
	  Recommended configuration
	  Outlook = 2030
	  Thunderbird = 8036
	  GMAIL = 8036
	 */
	'MAILTO_LIMIT' => 2030,
	// List of of modules from which you can choose e-mail address in the mail
	'RC_COMPOSE_ADDRESS_MODULES' => ['Accounts', 'Contacts', 'OSSEmployees', 'Leads', 'Vendors', 'Partners', 'Competition'],
	// What status should be set when a new mail is received regarding a ticket, whose status is awaiting response.
	'HELPDESK_NEXT_WAIT_FOR_RESPONSE_STATUS' => 'Answered',
	// What status should be set when a ticket is closed, but a new mail regarding the ticket is received.
	'HELPDESK_OPENTICKET_STATUS' => 'Open',
	// Required acceptation before sending mails
	'MAILER_REQUIRED_ACCEPTATION_BEFORE_SENDING' => false,
	// Write one row to s_yf_mail_sent_log per send attempt (MailerTask)
	'MAIL_AUDIT_LOG_ENABLED' => true,
	// Days to retain rows in s_yf_mail_sent_log (CleanupMailAuditLogTask)
	'AUDIT_LOG_RETENTION_DAYS' => 365,
	// Route relation workflow emails through the delayed buffer
	'DELAYED_EMAIL_BUFFER_ENABLED' => true,
	// Default delay when enqueue() omits explicit minutes
	'DELAYED_EMAIL_DEFAULT_MINUTES' => 3,
	// Only @domain recipients receive SMTP; other addresses are stripped; rows with no match are drained. Empty = disabled.
	'MAIL_FILTER_SEND_ONLY_TO_DOMAIN' => 'itconnect.pl',
];
