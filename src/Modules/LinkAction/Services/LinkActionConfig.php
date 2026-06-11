<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\LinkAction\Services;

final class LinkActionConfig
{
	public static function get(?string $key = null): mixed
	{
		if ($key === null) {
			return \App\Core\AppConfig::module('LinkAction') ?: [];
		}
		return \App\Core\AppConfig::module('LinkAction', $key);
	}

	public static function moduleConfig(string $moduleName): ?array
	{
		$modules = self::get('modules');
		if (!is_array($modules) || !isset($modules[$moduleName])) {
			return null;
		}
		return $modules[$moduleName];
	}

	public static function isActionAllowed(string $moduleName, string $action, string $scope): bool
	{
		$config = self::moduleConfig($moduleName);
		if ($config === null || !isset($config['actions'][$action])) {
			return false;
		}
		$scopes = $config['actions'][$action]['scopes'] ?? [];
		return in_array($scope, $scopes, true);
	}

	public static function isEmailFieldAllowed(string $moduleName, string $emailField): bool
	{
		$config = self::moduleConfig($moduleName);
		if ($config === null) {
			return false;
		}
		$fields = $config['email_fields'] ?? [];
		return in_array($emailField, $fields, true);
	}

	public static function handlerClass(string $moduleName, string $action): ?string
	{
		$config = self::moduleConfig($moduleName);
		if ($config === null || !isset($config['actions'][$action]['handler'])) {
			return null;
		}
		return (string) $config['actions'][$action]['handler'];
	}

	public static function deferredCustomToken(string $parserName, string $action, string $scope, string $emailField): string
	{
		return '$' . '(custom : ' . $parserName . '|' . $action . '|' . $scope . '|' . $emailField . ')$';
	}
}
