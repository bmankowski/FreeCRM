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

namespace App\Modules\ProjektyRekrutacyjne\Services\Verama;

final class VeramaJobFileOperations
{
	public static function moveToProcessed(string $jsonFilePath): void
	{
		if (!is_file($jsonFilePath)) {
			return;
		}
		$dest = VeramaJobFilePaths::processed() . basename($jsonFilePath);
		if (!rename($jsonFilePath, $dest)) {
			throw new \RuntimeException('Failed to move to processed: ' . $jsonFilePath);
		}
	}

	public static function moveToFailed(string $jsonFilePath): void
	{
		if (!is_file($jsonFilePath)) {
			return;
		}
		$dest = VeramaJobFilePaths::failed() . basename($jsonFilePath);
		if (!rename($jsonFilePath, $dest)) {
			throw new \RuntimeException('Failed to move to failed: ' . $jsonFilePath);
		}
	}
}
