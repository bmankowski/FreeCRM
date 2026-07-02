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

		$this->applyUniqueActiveServiceContract($recordModel);

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

	private function applyUniqueActiveServiceContract(\App\Modules\Base\Models\Record $recordModel): void
	{
		if ((int) $recordModel->get('servicecontractsid') > 0) {
			return;
		}
		$parentId = (int) $recordModel->get('parent_id');
		if ($parentId <= 0) {
			return;
		}
		if (!\App\Core\AppConfig::module('HelpDesk', 'CHECK_SERVICE_CONTRACTS_EXISTS')) {
			return;
		}
		$serviceContractsModule = \App\Modules\Base\Models\Module::getInstance('ServiceContracts');
		if ($serviceContractsModule === false || !$serviceContractsModule->isActive()) {
			return;
		}

		/** @var \App\Modules\HelpDesk\Models\Record $recordModel */
		$contracts = $recordModel->getActiveServiceContracts();
		if (count($contracts) !== 1) {
			return;
		}

		$recordModel->set('servicecontractsid', (int) $contracts[0]['servicecontractsid']);
	}
}
