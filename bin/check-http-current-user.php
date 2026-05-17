#!/usr/bin/env php
<?php
/**
 * CI guard: forbid static current-user accessors in HTTP layer (item 4).
 *
 * Usage: php bin/check-http-current-user.php
 * Exit 0 = clean, 1 = violations found.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$patterns = [
	'Record::getCurrentUserModel',
	'Record::getCurrentUserId',
	'CurrentUser::get(',
	'CurrentUser::getId(',
];

$globPaths = [
	$root . '/src/Modules/*/Actions',
	$root . '/src/Modules/*/*/Actions',
	$root . '/src/Modules/*/Views',
	$root . '/src/Modules/*/*/Views',
	$root . '/src/Modules/*/Controllers',
	$root . '/src/Modules/*/*/Controllers',
];

$files = [];
foreach ($globPaths as $globPath) {
	foreach (glob($globPath) ?: [] as $dir) {
		if (!is_dir($dir)) {
			continue;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
		);
		foreach ($iterator as $file) {
			if ($file->isFile() && $file->getExtension() === 'php') {
				$files[$file->getPathname()] = true;
			}
		}
	}
}

$violations = [];
foreach (array_keys($files) as $file) {
	$lines = file($file, FILE_IGNORE_NEW_LINES);
	foreach ($lines as $num => $line) {
		foreach ($patterns as $pattern) {
			if (str_contains($line, $pattern)) {
				$violations[] = sprintf(
					"%s:%d: %s",
					str_replace($root . '/', '', $file),
					$num + 1,
					trim($line)
				);
			}
		}
	}
}

if ($violations === []) {
	echo "OK: no banned current-user accessors in Actions/Views/Controllers.\n";
	exit(0);
}

echo "Banned current-user accessors in HTTP layer:\n";
foreach ($violations as $v) {
	echo "  $v\n";
}
echo count($violations) . " violation(s).\n";
exit(1);
