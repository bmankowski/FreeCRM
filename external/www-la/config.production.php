<?php
/**
 * LinkAction www endpoint configuration for open_basedir-limited hosting.
 *
 * Deploy to public_html/_link_action/config.php; directory must stay blocked by .htaccess.
 */
return [
	'public_keys' => [
		'v1' => __DIR__ . '/keys/link_action_public_v1.pem',
	],
	'queue_path' => __DIR__ . '/queue.jsonl',
	'pull_api_key' => 'generate-with-openssl-rand-hex-32',
	'pull_log_path' => __DIR__ . '/pull.log',
	'pull_rate_limit' => [
		'window_seconds' => 60,
		'max_requests' => 10,
		'storage_path' => __DIR__ . '/pull_rate_limit.json',
	],
	'reject_log_path' => __DIR__ . '/reject.log',
	'jti_cache_path' => __DIR__ . '/jti.cache',
	'rate_limit' => [
		'window_seconds' => 60,
		'max_requests' => 30,
		'storage_path' => __DIR__ . '/rate_limit.json',
	],
	'logo_asset_path' => __DIR__ . '/../assets/logo.png',
	'img_rate_limit' => [
		'window_seconds' => 60,
		'max_requests' => 500,
		'storage_path' => __DIR__ . '/img_rate_limit.json',
	],
	'modules' => [
		'Kandydaci' => [
			'actions' => [
				'unsubscribe' => [
					'scopes' => ['future_contact', 'all'],
					'response' => 'unsubscribe_ok',
				],
				'open' => [
					'scopes' => ['email'],
				],
			],
		],
	],
];
