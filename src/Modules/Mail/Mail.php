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

namespace App\Modules\Mail;

class Mail
{
	public function vtlib_handler(string $moduleName, string $eventType): void
	{
		if ($eventType === 'module.postinstall') {
			$encryption = new \App\Security\Encryption();
			if (!$encryption->isActive()) {
				\App\Log\Log::warning('Mail module: App\Security\Encryption is not active');
			}
		}
	}
}
