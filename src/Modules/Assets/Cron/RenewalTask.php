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

namespace App\Modules\Assets\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class RenewalTask extends AbstractCronTask
{
	public function execute(): void
	{
		$db = \App\Database\PearDatabase::getInstance();

		$renewal = ['PLL_PLANNED', 'PLL_WAITING_FOR_RENEWAL', ''];
		$query = 'SELECT 
			vtiger_assets.assetsid 
		  FROM
			vtiger_assets 
			INNER JOIN vtiger_crmentity 
			  ON vtiger_crmentity.crmid = vtiger_assets.assetsid 
		  WHERE vtiger_crmentity.deleted = 0 
			AND assets_renew IN (%s) OR assets_renew IS NULL';
		$query = sprintf($query, $db->generateQuestionMarks($renewal));
		$result = $db->pquery($query, $renewal);
		while (($recordId = $db->getSingleValue($result)) !== false) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, 'Assets');
			/** @var \App\Modules\Assets\Models\Record $recordModel */
			$recordModel->updateRenewal();
		}
	}
}
