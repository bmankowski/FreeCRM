<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\RecruitmentApplication\Services\CvImport;

final class CvImportLock
{
	/** @var resource|null */
	private $handle = null;

	public function acquire(int $waitSeconds = 300): bool
	{
		CvFilePaths::ensureDirectories();
		$this->handle = fopen(CvFilePaths::lockFile(), 'c+');
		if ($this->handle === false) {
			return false;
		}
		$deadline = time() + max(0, $waitSeconds);
		do {
			if (flock($this->handle, LOCK_EX | LOCK_NB)) {
				return true;
			}
			if ($waitSeconds <= 0) {
				return false;
			}
			sleep(1);
		} while (time() < $deadline);

		return false;
	}

	public function release(): void
	{
		if ($this->handle !== null) {
			flock($this->handle, LOCK_UN);
			fclose($this->handle);
			$this->handle = null;
		}
	}
}
