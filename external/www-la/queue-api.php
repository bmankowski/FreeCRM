<?php
/**
 * Authenticated queue API for CRM import (/la/queue).
 *
 * GET returns pending JSON Lines when X-LinkAction-Pull-Key matches; POST truncates after ack.
 * Unauthorized or misconfigured requests receive 404 (see Pull::hide).
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use FreeCRM\LinkAction\Www\Config;
use FreeCRM\LinkAction\Www\Pull;
use FreeCRM\LinkAction\Www\Queue;
use FreeCRM\LinkAction\Www\RateLimit;

$root = __DIR__;
$config = Config::load($root);
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (!Pull::verifyKey($config)) {
	Pull::log($config, $method, false);
	Pull::hide();
}

if (RateLimit::exceeded($config, 'pull_rate_limit')) {
	Pull::log($config, $method . '_rate_limited', true);
	Pull::hide();
}

Pull::log($config, $method, true);

$queuePath = (string) ($config['queue_path'] ?? '');
if ($queuePath === '') {
	Pull::hide();
}

if ($method === 'GET') {
	$contents = Queue::read($queuePath);
	if ($contents === null) {
		http_response_code(500);
		exit;
	}
	if ($contents === '') {
		http_response_code(204);
		exit;
	}
	header('Content-Type: application/x-ndjson; charset=utf-8');
	echo $contents;
	exit;
}

if ($method === 'POST') {
	if (!Queue::truncate($queuePath)) {
		http_response_code(500);
		exit;
	}
	http_response_code(204);
	exit;
}

Pull::hide();
