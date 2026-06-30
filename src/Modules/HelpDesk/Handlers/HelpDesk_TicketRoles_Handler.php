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

namespace App\Modules\HelpDesk\Handlers;

use App\Modules\HelpDesk\Models\DetailView;

class HelpDesk_TicketRoles_Handler
{
	public function entityBeforeSave(\App\Events\EventHandler $eventHandler): void
	{
		$recordModel = $eventHandler->getRecordModel();
		if ($recordModel->getModuleName() !== 'HelpDesk') {
			return;
		}

		if ($recordModel->isNew() && empty($recordModel->get('business_id'))) {
			$reporterId = (int) $recordModel->get('created_user_id');
			if ($reporterId <= 0) {
				$reporterId = (int) (\App\User\CurrentUser::getId() ?? 0);
			}
			if ($reporterId > 0) {
				$recordModel->set('business_id', $reporterId);
			}
		}

		$developerId = (int) $recordModel->get('developer_id');
		if ($developerId <= 0) {
			return;
		}

		$previousDeveloper = $recordModel->getPreviousValue('developer_id');
		$developerChanged = $recordModel->isNew()
			|| ($previousDeveloper !== null && $previousDeveloper !== false && (int) $previousDeveloper !== $developerId);
		if (!$developerChanged) {
			return;
		}

		$status = (string) $recordModel->get('ticketstatus');
		if (!DetailView::isDevActiveStatus($status)) {
			return;
		}

		$recordModel->set('assigned_user_id', $developerId);
	}
}
