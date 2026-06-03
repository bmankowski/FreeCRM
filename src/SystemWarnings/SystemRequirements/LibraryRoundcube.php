<?php
/**
 * FreeCRM - legacy Roundcube library check (removed with OSS mail stack).
 */

namespace App\SystemWarnings\SystemRequirements;

class LibraryRoundcube extends \App\SystemWarnings\Template
{
	public function process(): void
	{
		$this->status = 0;
	}
}
