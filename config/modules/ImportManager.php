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

$CONFIG = [
	'fileLimits' => [
		// Maximum upload size expressed both as MB (for UI) and bytes (for validation logic)
		'maxUploadSizeMb' => 10,
		'maxUploadSizeBytes' => 10 * 1024 * 1024,
		'allowedExtensions' => ['csv', 'xml', 'zip'],
		// Upper bound for server-side parsing (seconds)
		'parseTimeout' => 60,
	],
	'preview' => [
		'rows' => 30,
		// Allow users to paginate preview results in future iterations
		'maxPages' => 5,
	],
	'staging' => [
		// Default chunk size for BatchProcessor – can be tweaked per environment
		'chunkSize' => 200,
		'tablePrefix' => 'import_stage_',
		'validation' => [
			'stopOnFirstError' => false,
			'normalizeWhitespace' => true,
		],
	],
	'queue' => [
		'driver' => 'vtiger_import_queue',
		// When to enqueue automatically instead of running inline
		'thresholds' => [
			'records' => 1000,
			'fileSizeMb' => 5,
		],
	],
	'cleanup' => [
		// Retention policy in days for files, staging tables and log entries
		'retentionDays' => 2,
		'cronTime' => '02:00',
		'maxBatchesPerRun' => 50,
	],
];

