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

namespace App\Modules\OSSSoldServices\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class RenewalTask extends AbstractCronTask
{
	public function execute(): void
	{
		$db = \App\Database\PearDatabase::getInstance();

		$renewal = ['PLL_PLANNED', 'PLL_WAITING_FOR_RENEWAL', ''];
		$query = sprintf('SELECT 
					vtiger_osssoldservices.osssoldservicesid 
				  FROM
					vtiger_osssoldservices 
					INNER JOIN vtiger_crmentity 
					  ON vtiger_crmentity.crmid = vtiger_osssoldservices.osssoldservicesid 
				  WHERE vtiger_crmentity.deleted = 0 
					AND osssoldservices_renew IN (%s) OR osssoldservices_renew IS NULL', $db->generateQuestionMarks($renewal));
		$result = $db->pquery($query, $renewal);
		while (($recordId = $db->getSingleValue($result)) !== false) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, 'OSSSoldServices');
			$recordModel->updateRenewal();
		}
	}
}
