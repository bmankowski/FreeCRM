<?php
/**
 * FreeCRM - LinkAction module configuration.
 */

$linkActionQueueApiKey = getenv('FREECRM_LINK_ACTION_QUEUE_API_KEY');
if ($linkActionQueueApiKey === false || $linkActionQueueApiKey === '') {
	$keyFile = ROOT_DIRECTORY . '/config/keys/link_action_queue_api_key.txt';
	if (is_readable($keyFile)) {
		$linkActionQueueApiKey = trim((string) file_get_contents($keyFile));
	} else {
		$linkActionQueueApiKey = '';
	}
}

$CONFIG = [
	'active_kid' => 'v1',
	'private_key_path' => ROOT_DIRECTORY . '/config/keys/link_action_private_v1.pem',
	'public_keys' => [
		'v1' => ROOT_DIRECTORY . '/config/keys/link_action_public_v1.pem',
	],
	'token_ttl_seconds' => 63072000,
	'email_pepper' => 'link-action-email-pepper-change-in-production',
	'www_base_url' => 'https://www.itconnect.pl/la',
	'iat_skew_seconds' => 60,
	'modules' => [
		'Kandydaci' => [
			'default_email_field' => 'newsletter_email',
			'email_fields' => ['newsletter_email', 'email_prywatny'],
			'actions' => [
				'unsubscribe' => [
					'handler' => 'App\\Modules\\LinkAction\\Services\\Handlers\\KandydaciUnsubscribeHandler',
					'scopes' => ['future_contact', 'all'],
				],
				'open' => [
					'handler' => 'App\\Modules\\LinkAction\\Services\\Handlers\\KandydaciOpenHandler',
					'scopes' => ['email'],
				],
			],
		],
	],
	'queue_api' => [
		'fetch_url' => 'https://www.itconnect.pl/la/queue',
		'ack_url' => 'https://www.itconnect.pl/la/queue',
		'api_key' => (string) $linkActionQueueApiKey,
		'timeout_seconds' => 30,
		'local_incoming' => ROOT_DIRECTORY . '/import/link-action/incoming/queue.jsonl',
	],
];
