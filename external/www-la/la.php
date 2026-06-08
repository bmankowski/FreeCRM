<?php
/**
 * Public LinkAction click endpoint (/la?t=…).
 *
 * Verifies the CRM-signed token, enforces rate limits and replay protection,
 * appends a JSON Lines row for CRM import, then renders the configured HTML response.
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use FreeCRM\LinkAction\Www\Config;
use FreeCRM\LinkAction\Www\Queue;
use FreeCRM\LinkAction\Www\RateLimit;
use FreeCRM\LinkAction\Www\Registry;
use FreeCRM\LinkAction\Www\Replay;
use FreeCRM\LinkAction\Www\Response;
use FreeCRM\LinkAction\Www\Token;

$root = __DIR__;
$config = Config::load($root);

$token = trim((string) ($_GET['t'] ?? ''));
if ($token === '') {
	Response::reject($root, $config, 'missing_token');
}
if (RateLimit::exceeded($config)) {
	Response::reject($root, $config, 'rate_limited');
}

$payload = Token::verify($token, $config);
if ($payload === null) {
	Response::reject($root, $config, 'invalid_token');
}

$response = Registry::response($payload, $config);
if ($response === null) {
	Response::reject($root, $config, 'unregistered_action');
}

$jti = (string) ($payload['jti'] ?? '');
if ($jti !== '' && Replay::seen($jti, $config)) {
	Response::render($root, $response);
}

if (!Queue::append($token, $config)) {
	Response::reject($root, $config, 'queue_write_failed');
}

Response::render($root, $response);
