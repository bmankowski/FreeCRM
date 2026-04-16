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

namespace App\Modules\OpenStreetMap\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class UpdaterCoordinatesTask extends AbstractCronTask
{
	public function execute(): void
	{
		$db = \App\Db\Db::getInstance();
		$lastUpdatedCrmId = (new \App\Db\Query())->select(['crmid'])
			->from('u_#__openstreetmap_address_updater')
			->scalar();
		if ($lastUpdatedCrmId === false) {
			return;
		}
		$dataReader = (new \App\Db\Query())->select(['crmid', 'setype', 'deleted'])
				->from('vtiger_crmentity')
				->where(['>', 'crmid', $lastUpdatedCrmId])
				->limit(\App\Core\AppConfig::module('OpenStreetMap', 'CRON_MAX_UPDATED_ADDRESSES'))
				->createCommand()->query();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('OpenStreetMap');
		$coordinatesModel = \App\Modules\OpenStreetMap\Models\Coordinate::getInstance();
		while ($row = $dataReader->read()) {
			if ($moduleModel->isAllowModules($row['setype']) && $row['deleted'] == 0) {
				$recordModel = \App\Modules\Base\Models\Record::getInstanceById($row['crmid']);
				$coordinates = $coordinatesModel->getCoordinatesByRecord($recordModel);
				foreach ($coordinates as $typeAddress => $coordinate) {
					$isCoordinateExists = (new \App\Db\Query())->from('u_#__openstreetmap')->where(['type' => $typeAddress, 'crmid' => $recordModel->getId()])->exists();
					if ($isCoordinateExists) {
						if (empty($coordinate['lat']) && empty($coordinate['lon'])) {
							$db->createCommand()->delete('u_#__openstreetmap', ['type' => $typeAddress, 'crmid' => $recordModel->getId()])->execute();
						} else {
							$db->createCommand()->update('u_#__openstreetmap', $coordinate, ['type' => $typeAddress, 'crmid' => $recordModel->getId()])->execute();
						}
					} else {
						if (!empty($coordinate['lat']) && !empty($coordinate['lon'])) {
							$coordinate['type'] = $typeAddress;
							$coordinate['crmid'] = $recordModel->getId();
							$db->createCommand()->insert('u_#__openstreetmap', $coordinate)->execute();
						}
					}
				}
			}
			$lastUpdatedCrmId = $row['crmid'];
		}
		$lastRecordId = $db->getUniqueID('vtiger_crmentity', 'crmid', false);
		$cronTask = \vtlib\Cron::getInstance('LBL_UPDATER_COORDINATES');
		if ($cronTask === null) {
			return;
		}
		if ($lastRecordId === $lastUpdatedCrmId) {
			$db->createCommand()->update('u_#__openstreetmap_address_updater', ['crmid' => $lastUpdatedCrmId])->execute();
			$cronTask->updateStatus(\vtlib\Cron::STATUS_DISABLED);
			$cronTask->set('lockStatus', true);
		} else {
			$db->createCommand()->update('u_#__openstreetmap_address_updater', ['crmid' => $lastUpdatedCrmId])->execute();
		}
	}
}
