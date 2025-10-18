<?php

namespace App\Modules\OSSMailScanner\Models;

/**
 * Base for action creating relations on the basis of mail address
 * @package YetiForce.MailScanner
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class EmailScannerAction {

	public function process(OSSMail_Mail_Model $mail, $moduleName)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$mailId = $mail->getMailCrmId();
		if (!$mailId) {
			return 0;
		}

		$crmIds = $mail->findEmailAdress('fromaddress', $moduleName, true);
		$crmidsToaddress = $mail->findEmailAdress('toaddress', $moduleName, true);
		$crmidsCcaddress = $mail->findEmailAdress('ccaddress', $moduleName, true);
		$crmidsBccaddress = $mail->findEmailAdress('bccaddress', $moduleName, true);
		$crmidsReplyToaddress = $mail->findEmailAdress('reply_toaddress', $moduleName, true);
		$crmIds = \App\Modules\OSSMailScanner\Models\Record::_merge_array($crmIds, $crmidsToaddress);
		$crmIds = \App\Modules\OSSMailScanner\Models\Record::_merge_array($crmIds, $crmidsCcaddress);
		$crmIds = \App\Modules\OSSMailScanner\Models\Record::_merge_array($crmIds, $crmidsBccaddress);
		$crmIds = \App\Modules\OSSMailScanner\Models\Record::_merge_array($crmIds, $crmidsReplyToaddress);
		$returnIds = [];

		if (!empty($crmIds)) {
			$relationModel = new OSSMailView_Relation_Model();
			foreach ($crmIds as $crmId) {
				$status = $relationModel->addRelation($mailId, $crmId, $mail->get('udate_formated'));
				if ($status) {
					$returnIds[] = $crmId;
				}
			}
		}
		return $returnIds;
	}
}
