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
