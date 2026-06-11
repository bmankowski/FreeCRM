<?php
/**
 * LinkAction www endpoint configuration example.
 *
 * Copy to ../private/la/config.php when the host allows reading outside public_html,
 * or use config.production.php paths under _link_action/ instead.
 */
return [
	'public_keys' => [
		'v1' => __DIR__ . '/../private/keys/link_action_public_v1.pem',
	],
	'queue_path' => __DIR__ . '/../private/la/queue.jsonl',
	'pull_api_key' => 'generate-with-openssl-rand-hex-32',
	'pull_log_path' => __DIR__ . '/../private/la/pull.log',
	'pull_rate_limit' => [
		'window_seconds' => 60,
		'max_requests' => 10,
		'storage_path' => __DIR__ . '/../private/la/pull_rate_limit.json',
	],
	'reject_log_path' => __DIR__ . '/../private/la/reject.log',
	'jti_cache_path' => __DIR__ . '/../private/la/jti.cache',
	'rate_limit' => [
		'window_seconds' => 60,
		'max_requests' => 30,
		'storage_path' => __DIR__ . '/../private/la/rate_limit.json',
	],
	'modules' => [
		'Candidates' => [
			'actions' => [
				'unsubscribe' => [
					'scopes' => ['future_contact', 'all'],
					'redirect_url' => 'https://www.itconnect.pl/wypisanie-zakonczone/',
				],
				'resubscribe' => [
					'scopes' => ['future_contact'],
					'redirect_url' => 'https://www.itconnect.pl/zapis-ponowny-do-bazy-mailingowej/',
				],
			],
		],
	],
];
