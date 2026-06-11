<?php
/**
 * Public LinkAction open-tracking image endpoint (/la/o/{token}/logo.png).
 *
 * Verifies the CRM-signed token, enqueues the first hit, then serves the static tracking PNG.
 * Invalid or expired tokens still return the pixel without recording an open.
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use FreeCRM\LinkAction\Www\Config;
use FreeCRM\LinkAction\Www\ImageResponse;
use FreeCRM\LinkAction\Www\Queue;
use FreeCRM\LinkAction\Www\RateLimit;
use FreeCRM\LinkAction\Www\Registry;
use FreeCRM\LinkAction\Www\Replay;
use FreeCRM\LinkAction\Www\Token;

$root = __DIR__;
$config = Config::load($root);
$logoPath = (string) ($config['logo_asset_path'] ?? $root . '/assets/pixel.gif');

$token = trim((string) ($_GET['t'] ?? ''));
if ($token === '') {
	ImageResponse::serve($logoPath);
}

$payload = Token::verify($token, $config);
if ($payload === null || !Registry::isAllowed($payload, $config)) {
	ImageResponse::serve($logoPath);
}

RateLimit::exceeded($config, 'img_rate_limit');

$jti = (string) ($payload['jti'] ?? '');
if ($jti !== '' && Replay::seen($jti, $config)) {
	ImageResponse::serve($logoPath);
}

Queue::append($token, $config);

ImageResponse::serve($logoPath);
