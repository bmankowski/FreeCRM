#!/usr/bin/env php
<?php
/**
 * FreeCRM - Move storage MultiAttachment/MultiImage Kandydaci dirs to Candidates on disk.
 *
 * Run: docker compose exec -T app php bin/migrate_candidates_storage_paths.php
 * Called from migrations/Users/m260609_000004_rename_kandydaci_storage_and_template_codes.php
 */

declare(strict_types=1);

if (!defined('ROOT_DIRECTORY')) {
	define('ROOT_DIRECTORY', dirname(__DIR__));
}

/** @return list<string> */
function migrateCandidatesStoragePaths(bool $dryRun = false): array
{
	$storageRoot = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'storage';
	$renames = [
		'MultiAttachment' . DIRECTORY_SEPARATOR . 'Kandydaci' => 'MultiAttachment' . DIRECTORY_SEPARATOR . 'Candidates',
		'MultiImage' . DIRECTORY_SEPARATOR . 'Kandydaci' => 'MultiImage' . DIRECTORY_SEPARATOR . 'Candidates',
	];
	$messages = [];

	foreach ($renames as $fromRel => $toRel) {
		$from = $storageRoot . DIRECTORY_SEPARATOR . $fromRel;
		$to = $storageRoot . DIRECTORY_SEPARATOR . $toRel;

		if (!is_dir($from)) {
			if (is_dir($to)) {
				$messages[] = "Skip (already migrated): $fromRel → $toRel";
				continue;
			}
			$messages[] = "Skip (source missing): $fromRel";
			continue;
		}
		if (is_dir($to)) {
			throw new \RuntimeException("Cannot rename $fromRel: destination already exists ($toRel)");
		}

		if ($dryRun) {
			$messages[] = "Would rename: $fromRel → $toRel";
			continue;
		}

		if (!@rename($from, $to)) {
			throw new \RuntimeException("Failed to rename storage directory: $fromRel → $toRel");
		}
		$messages[] = "Renamed: $fromRel → $toRel";
	}

	return $messages;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
	$dryRun = in_array('--dry-run', $argv ?? [], true);
	try {
		foreach (migrateCandidatesStoragePaths($dryRun) as $line) {
			echo $line . "\n";
		}
		exit(0);
	} catch (\Throwable $e) {
		fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
		exit(1);
	}
}
