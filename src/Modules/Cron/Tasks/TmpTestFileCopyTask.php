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

namespace App\Modules\Cron\Tasks;

/**
 * Copies cache/tmp/test/test.txt to cache/tmp/test/test_YYYYMMDD-HHMM.txt (for cron testing).
 * Example: test_20260419-0730.txt (24-hour clock, no colon in filename).
 */
final class TmpTestFileCopyTask extends AbstractCronTask
{
	public function execute(): void
	{
		if (!defined('ROOT_DIRECTORY') || ROOT_DIRECTORY === '') {
			$this->log('TmpTestFileCopyTask: ROOT_DIRECTORY is not defined.', 'warning');
			return;
		}

		$baseDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'test';
		$source = $baseDir . DIRECTORY_SEPARATOR . 'test.txt';
		$dest = $baseDir . DIRECTORY_SEPARATOR . 'test_' . date('Ymd-Hi') . '.txt';

		if (!is_file($source)) {
			$this->log('TmpTestFileCopyTask: source file missing: ' . $source, 'trace');
			return;
		}

		if (!copy($source, $dest)) {
			$this->log('TmpTestFileCopyTask: copy failed: ' . $source . ' -> ' . $dest, 'error');
		}
	}
}
