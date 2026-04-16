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

namespace App\Modules\OSSMailScanner\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class MailScannerBindTask extends AbstractCronTask
{
	public function execute(): void
	{
		$db = \App\Database\PearDatabase::getInstance();
		$scanerModel = \App\Modules\Base\Models\Record::getCleanInstance('OSSMailScanner');
		$result = $db->query("SELECT vtiger_ossmailview.*,roundcube_users.actions FROM vtiger_ossmailview INNER JOIN roundcube_users ON roundcube_users.user_id = vtiger_ossmailview.rc_user WHERE vtiger_ossmailview.verify = 1");
		while ($row = $db->getRow($result)) {
			$scanerModel->bindMail($row);
			$db->update('vtiger_ossmailview', [
				'verify' => 0
				], 'ossmailviewid = ?', [$row['ossmailviewid']]
			);
		}
		$bindByEmail = ['Leads', 'Accounts', 'Partners', 'Vendors', 'Competition', 'Contacts', 'OSSEmployees'];
		$bindByPrefix = ['Campaigns', 'HelpDesk', 'Project', 'SSalesProcesses'];
		$result = $db->query('SELECT * FROM s_yf_mail_relation_updater');
		while ($relationRow = $db->getRow($result)) {
			$db->delete('vtiger_ossmailview_relation', 'crmid = ?', [$relationRow['crmid']]);
			$moduleName = \App\Utils\ModuleUtils::getModuleName($relationRow['tabid']);
			$bind = false;
			if (in_array($moduleName, $bindByEmail)) {
				$bind = 'email';
			}
			if (in_array($moduleName, $bindByPrefix)) {
				$bind = 'prefix';
			}
			if ($bind === false) {
				continue;
			}
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($relationRow['crmid'], $moduleName);
			$where = [];
			if ($bind === 'prefix') {
				$recordNumber = $recordModel->getRecordNumber();
				if (empty($recordNumber)) {
					continue;
				}
				$where[] = "subject LIKE '%[$recordNumber]%'";
			} elseif ($bind === 'email') {
				$fieldModels = $recordModel->getModule()->getFieldsByType('email');
				foreach ($fieldModels as $fieldName => $fieldModel) {
					if (!$recordModel->isEmpty($fieldName)) {
						$email = $recordModel->get($fieldName);
						$where[] = "from_email = '$email' OR to_email = '$email' OR cc_email = '$email' OR bcc_email = '$email' ";
					}
				}
			}
			if (!empty($where)) {
				$query = 'SELECT vtiger_ossmailview.*,roundcube_users.actions FROM vtiger_ossmailview INNER JOIN roundcube_users ON roundcube_users.user_id = vtiger_ossmailview.rc_user WHERE ';
				$query .= implode(' OR ', $where);
				$resultMail = $db->query($query);
				if ($db->getRowCount($resultMail)) {
					while ($row = $db->getRow($resultMail)) {
						$scanerModel->bindMail($row);
					}
				}
			}
			$db->delete('s_yf_mail_relation_updater', 'crmid = ?', [$relationRow['crmid']]);
		}
	}
}
