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

namespace App\Modules\RecruitmentApplication\Services\CvImport;

final class CvImportLock
{
	/** @var resource|null */
	private $handle = null;

	public function acquire(): bool
	{
		CvFilePaths::ensureDirectories();
		$this->handle = fopen(CvFilePaths::lockFile(), 'c+');
		if ($this->handle === false) {
			return false;
		}
		return flock($this->handle, LOCK_EX | LOCK_NB);
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
