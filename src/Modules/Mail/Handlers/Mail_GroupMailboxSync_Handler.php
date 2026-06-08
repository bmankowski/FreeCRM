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

class Mail_GroupMailboxSync_Handler
{
	public function groupAfterSave(\App\Events\EventHandler $eventHandler): void
	{
		$params = $eventHandler->getParams();
		$groupsRecordModel = $params['groupsRecordModel'] ?? null;
		if (!$groupsRecordModel instanceof \App\Modules\Settings\Groups\Models\Record) {
			return;
		}

		$groupId = (int) $groupsRecordModel->getId();
		if ($groupId <= 0) {
			return;
		}

		\App\Modules\Mail\Models\Account::syncForGroup($groupId);
	}
}
