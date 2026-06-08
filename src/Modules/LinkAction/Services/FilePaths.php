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

final class FilePaths
{
	public static function base(): string
	{
		return rtrim(ROOT_DIRECTORY, '/') . '/import/link-action/';
	}

	public static function incoming(): string
	{
		return self::base() . 'incoming/';
	}

	public static function processed(): string
	{
		return self::base() . 'processed/';
	}

	public static function failed(): string
	{
		return self::base() . 'failed/';
	}

	public static function incomingQueueFile(): string
	{
		$configured = LinkActionConfig::get('queue_api');
		if (is_array($configured) && !empty($configured['local_incoming'])) {
			return (string) $configured['local_incoming'];
		}
		return self::incoming() . 'queue.jsonl';
	}

	public static function lockFile(): string
	{
		return self::base() . '.import.lock';
	}

	public static function ensureDirectories(): void
	{
		foreach ([self::incoming(), self::processed(), self::failed()] as $dir) {
			if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
				throw new \RuntimeException('Could not create link-action directory: ' . $dir);
			}
		}
	}
}
