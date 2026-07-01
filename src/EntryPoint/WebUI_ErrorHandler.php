<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

namespace App\EntryPoint;

/**
 * Routes PHP runtime errors into App\Log (Yii FileTarget → cache/logs/system.log).
 */
class WebUI_ErrorHandler
{
	public static function handle(int $errno, string $errstr, string $errfile, int $errline): bool
	{
		$level = self::mapErrno($errno);
		if ($level === null) {
			return false;
		}

		if (\App\Core\AppConfig::debug('EXCEPTION_ERROR_TO_FILE')) {
			self::log($level, self::formatMessage($errstr, $errfile, $errline));
		}

		if (ob_get_level() === 0) {
			self::displayError(self::formatMessage($errstr, $errfile, $errline));
		}

		return true;
	}

	private static function mapErrno(int $errno): ?string
	{
		if ($errno & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_PARSE | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
			return 'error';
		}
		if ($errno & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING)) {
			return 'warning';
		}
		if ($errno & (E_NOTICE | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED)) {
			return 'warning';
		}

		return null;
	}

	private static function formatMessage(string $errstr, string $errfile, int $errline): string
	{
		$file = str_replace(ROOT_DIRECTORY . DIRECTORY_SEPARATOR, '', $errfile);

		return sprintf('%s in %s on line %d', $errstr, $file, $errline);
	}

	private static function log(string $level, string $message): void
	{
		if ($level === 'error') {
			\App\Log\Log::error($message, 'php');
			return;
		}

		\App\Log\Log::warning($message, 'php');
	}

	private static function displayError(string $message): void
	{
		if (!\App\Core\AppConfig::debug('EXCEPTION_ERROR_TO_SHOW')) {
			return;
		}

		\vtlib\Functions::throwNewException($message, false);
	}

	public static function register(): void
	{
		set_error_handler(
			[self::class, 'handle'],
			\App\Core\AppConfig::debug('EXCEPTION_ERROR_LEVEL')
		);
	}
}
