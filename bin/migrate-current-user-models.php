#!/usr/bin/env php
<?php
/**
 * Replace Record::getCurrentUser* with CurrentUser::get/getId in model & shared code.
 *
 * Usage:
 *   php bin/migrate-current-user-models.php --dry-run
 *   php bin/migrate-current-user-models.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv ?? [], true);

$scanDirs = [
	$root . '/src/Modules',
	$root . '/src/View',
	$root . '/src/Security',
	$root . '/src/Fields',
	$root . '/src/QueryField',
	$root . '/src/Records',
	$root . '/src/Core',
	$root . '/src/Email',
	$root . '/src/Events',
	$root . '/src/TextParser',
	$root . '/src/Runtime',
	$root . '/src/ModuleManagement',
];

$skipFiles = [
	'src/Modules/Users/Models/Record.php',
];

$replacements = [
	'\\App\\Modules\\Users\\Models\\Record::getCurrentUserModel()' => '\\App\\User\\CurrentUser::get()',
	'\\App\\Modules\\Users\\Models\\Record::getCurrentUserId()' => '(int) (\\App\\User\\CurrentUser::getId() ?? 0)',
];

$changed = 0;
foreach ($scanDirs as $dir) {
	if (!is_dir($dir)) {
		continue;
	}
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
	);
	foreach ($iterator as $file) {
		if (!$file->isFile() || $file->getExtension() !== 'php') {
			continue;
		}
		$rel = str_replace($root . '/', '', $file->getPathname());
		foreach ($skipFiles as $skip) {
			if ($rel === $skip) {
				continue 2;
			}
		}
		$content = file_get_contents($file->getPathname());
		$new = $content;
		foreach ($replacements as $from => $to) {
			$new = str_replace($from, $to, $new);
		}
		if ($new !== $content) {
			$changed++;
			echo ($dryRun ? '[dry-run] ' : '') . "update $rel\n";
			if (!$dryRun) {
				file_put_contents($file->getPathname(), $new);
			}
		}
	}
}

echo ($dryRun ? 'Would update' : 'Updated') . " $changed file(s).\n";
