<?php

namespace App\Modules\OSSMailView\Models;

/**
 * OSSMailView Relation mail
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Relation extends \App\Modules\Vtiger\Models\Relation
{

	public function addRelation($mailId, $crmid, $date = false)
	{
		$return = false;
		$db = \App\Database\PearDatabase::getInstance();
		\App\CRMEntity::trackLinkedInfo($crmid);
		$destinationModuleName = \App\Record::getType($crmid);
		$data = [
			'CRMEntity' => \App\CRMEntity::getInstance($destinationModuleName),
			'sourceModule' => $destinationModuleName,
			'sourceRecordId' => $crmid,
			'destinationModule' => 'OSSMailView',
			'destinationRecordId' => $mailId
		];
		$eventHandler = new \App\EventHandler();
		$eventHandler->setModuleName($destinationModuleName);
		$eventHandler->setParams($data);
		$eventHandler->trigger('EntityBeforeLink');

		$query = 'SELECT * FROM vtiger_ossmailview_relation WHERE ossmailviewid = ? && crmid = ?';
		$result = $db->pquery($query, [$mailId, $crmid]);
		if ($db->getRowCount($result) == 0) {
			if (!$date) {
				$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($mailId, 'OSSMailView');
				$date = $recordModel->get('date');
			}
			$db->insert('vtiger_ossmailview_relation', [
				'ossmailviewid' => $mailId,
				'crmid' => $crmid,
				'date' => $date
			]);

			if ($parentId = \App\Modules\Users\Models\Privileges::getParentRecord($crmid)) {
				$query = 'SELECT * FROM vtiger_ossmailview_relation WHERE ossmailviewid = ? && crmid = ?';
				$result = $db->pquery($query, [$mailId, $parentId]);
				if ($db->getRowCount($result) == 0) {
					$db->insert('vtiger_ossmailview_relation', [
						'ossmailviewid' => $mailId,
						'crmid' => $parentId,
						'date' => $date
					]);
					if ($parentId = \App\Modules\Users\Models\Privileges::getParentRecord($parentId)) {
						$query = 'SELECT * FROM vtiger_ossmailview_relation WHERE ossmailviewid = ? && crmid = ?';
						$result = $db->pquery($query, [$mailId, $parentId]);
						if ($db->getRowCount($result) == 0) {
							$db->insert('vtiger_ossmailview_relation', [
								'ossmailviewid' => $mailId,
								'crmid' => $parentId,
								'date' => $date
							]);
						}
					}
				}
			}
			$return = true;
		}
		$eventHandler->trigger('EntityAfterLink');
		return $return;
	}

	public function getAttachments()
	{
		$queryGenerator = $this->getQueryGenerator();
		$queryGenerator->addJoin(['LEFT JOIN', 'vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid = vtiger_notes.notesid']);
		$queryGenerator->addJoin(['LEFT JOIN', 'vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid']);
		$queryGenerator->addJoin(['LEFT JOIN', 'vtiger_ossmailview_files', 'vtiger_ossmailview_files.documentsid = vtiger_notes.notesid']);
		$queryGenerator->addNativeCondition(['vtiger_ossmailview_files.ossmailviewid' => $this->get('parentRecord')->getId()]);
	}
}
