#!/usr/bin/env php
<?php
/**
 * Inventory static current-user usage (models vs HTTP layer).
 *
 * Usage: php bin/audit-current-user.php [--csv]
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$patterns = [
	'Record::getCurrentUserModel' => 'Record::getCurrentUserModel',
	'Record::getCurrentUserId' => 'Record::getCurrentUserId',
	'CurrentUser::get' => 'CurrentUser::get(',
	'CurrentUser::getId' => 'CurrentUser::getId(',
];

$src = $root . '/src';
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
);

$rows = [];
foreach ($iterator as $file) {
	if (!$file->isFile() || $file->getExtension() !== 'php') {
		continue;
	}
	$path = $file->getPathname();
	$rel = str_replace($root . '/', '', $path);
	$layer = 'other';
	if (preg_match('#/Actions/|/Views/|/Controllers/#', $rel)) {
		$layer = 'http';
	} elseif (preg_match('#/Models/#', $rel)) {
		$layer = 'model';
	}

	$lines = file($path, FILE_IGNORE_NEW_LINES);
	foreach ($lines as $num => $line) {
		foreach ($patterns as $key => $needle) {
			if (str_contains($line, $needle)) {
				$rows[] = [$layer, $rel, $num + 1, $key, trim($line)];
			}
		}
	}
}

$csv = in_array('--csv', $argv ?? [], true);
if ($csv) {
	echo "layer,file,line,pattern,code\n";
	foreach ($rows as $row) {
		echo implode(',', array_map(static fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"', $row)) . "\n";
	}
	exit(0);
}

$byLayer = ['http' => 0, 'model' => 0, 'other' => 0];
foreach ($rows as $row) {
	$byLayer[$row[0]]++;
}

echo "Current-user static accessor inventory\n";
echo "  HTTP (Actions/Views/Controllers): {$byLayer['http']}\n";
echo "  Models: {$byLayer['model']}\n";
echo "  Other: {$byLayer['other']}\n";
echo "  Total: " . count($rows) . "\n\n";

if ($byLayer['http'] > 0) {
	echo "HTTP layer (fix with bin/check-http-current-user.php):\n";
	foreach ($rows as $row) {
		if ($row[0] === 'http') {
			echo "  {$row[1]}:{$row[2]} {$row[3]}\n";
		}
	}
}
