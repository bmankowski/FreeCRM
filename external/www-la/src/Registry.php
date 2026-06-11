<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class Registry
{
	public static function isAllowed(array $payload, array $config): bool
	{
		$module = (string) ($payload['module'] ?? '');
		$action = (string) ($payload['action'] ?? '');
		$scope = (string) ($payload['scope'] ?? '');
		$moduleConfig = $config['modules'][$module]['actions'][$action] ?? null;
		if (!is_array($moduleConfig)) {
			return false;
		}
		$scopes = $moduleConfig['scopes'] ?? [];

		return in_array($scope, $scopes, true);
	}

	public static function redirectUrl(array $payload, array $config): ?string
	{
		if (!self::isAllowed($payload, $config)) {
			return null;
		}
		$module = (string) ($payload['module'] ?? '');
		$action = (string) ($payload['action'] ?? '');
		$moduleConfig = $config['modules'][$module]['actions'][$action];
		$url = trim((string) ($moduleConfig['redirect_url'] ?? ''));

		return $url !== '' ? $url : null;
	}

	public static function redirectTarget(array $payload, array $config): ?string
	{
		$base = self::redirectUrl($payload, $config);
		if ($base === null) {
			return null;
		}
		if ((string) ($payload['action'] ?? '') !== 'unsubscribe') {
			return $base;
		}
		$rsToken = trim((string) ($payload['rs_t'] ?? ''));
		if ($rsToken === '') {
			return $base;
		}
		$rsPayload = Token::verify($rsToken, $config);
		if ($rsPayload === null || (string) ($rsPayload['action'] ?? '') !== 'resubscribe') {
			return $base;
		}
		if (!self::matchesPairedToken($payload, $rsPayload)) {
			return $base;
		}
		$sep = str_contains($base, '?') ? '&' : '?';

		return $base . $sep . 'rt=' . rawurlencode($rsToken);
	}

	/** @param array<string, mixed> $parent @param array<string, mixed> $child */
	private static function matchesPairedToken(array $parent, array $child): bool
	{
		foreach (['module', 'record_id', 'email_field', 'eh'] as $key) {
			if (($parent[$key] ?? null) !== ($child[$key] ?? null)) {
				return false;
			}
		}

		return true;
	}

	public static function response(array $payload, array $config): ?string
	{
		if (!self::isAllowed($payload, $config)) {
			return null;
		}
		$module = (string) ($payload['module'] ?? '');
		$action = (string) ($payload['action'] ?? '');
		$moduleConfig = $config['modules'][$module]['actions'][$action];

		return (string) ($moduleConfig['response'] ?? 'error');
	}
}
