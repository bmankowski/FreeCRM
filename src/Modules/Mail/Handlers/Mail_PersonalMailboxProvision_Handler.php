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

namespace App\Modules\Mail\Handlers;

class Mail_PersonalMailboxProvision_Handler
{
	public function entityAfterSave(\App\Events\EventHandler $eventHandler): void
	{
		if ($eventHandler->getModuleName() !== 'Users') {
			return;
		}

		$recordModel = $eventHandler->getRecordModel();
		$userId = (int) $recordModel->getId();
		if ($userId <= 0) {
			return;
		}

		$status = (string) $recordModel->get('status');
		if ($status !== '' && $status !== 'Active') {
			return;
		}

		\App\Modules\Mail\Models\Account::ensurePersonalAccountForUser($userId);
	}
}
