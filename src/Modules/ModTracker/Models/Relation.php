<?php

namespace App\Modules\ModTracker\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Relation extends \App\Modules\Base\Models\Relation
{
	/** @var mixed Parent object */
	protected $parent;

	public function getValue()
	{
		return $this->getLinkedRecord()->getName();
	}

	public function setParent($parent)
	{
		$this->parent = $parent;
	}

	public function getParent()
	{
		return $this->parent;
	}

	public function getLinkedRecord()
	{
		$db = \App\Database\PearDatabase::getInstance();

		$targetId = $this->get('targetid');
		$targetModule = $this->get('targetmodule');

		$query = 'SELECT * FROM vtiger_crmentity WHERE crmid = ?';
		$result = $db->pquery($query, [$targetId]);
		$noOfRows = $db->num_rows($result);
		$moduleModels = [];
		if ($noOfRows) {
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($targetModule);
			$row = $db->getRow($result);
			$modelClassName = \App\Core\Loader::getComponentClassName('Model', 'Record', $targetModule);
			$recordInstance = new $modelClassName();
			$recordInstance->setData($row)->setModuleFromInstance($moduleModel);
			$recordInstance->set('id', $row['crmid']);
			return $recordInstance;
		}
		return false;
	}

	/**
	 * Function adds records to task queue that updates reviewing changes in records
	 * @param array $data - List of records to update
	 * @param string $module - Module name
	 */
	public static function reviewChangesQueue($data, $module)
	{
		$db = \App\Db\Db::getInstance();
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$id = (new \App\Db\Query())->from('u_#__reviewed_queue')->max('id') + 1;
		$db->createCommand()->insert('u_#__reviewed_queue', [
			'id' => $id,
			'userid' => $currentUserModel->getRealId(),
			'tabid' => \vtlib\Functions:: getModuleId($module),
			'data' => \App\Utils\Json::encode($data),
			'time' => date('Y-m-d H:i:s')
		])->execute();
	}

	/**
	 * Function marks forwarded records as reviewed
	 * @param array $recordsList - List of records to update
	 * @param integer $userId - User id
	 */
	public static function reviewChanges($recordsList, $userId = false)
	{
		foreach ($recordsList as $record) {
			$result = \App\Modules\ModTracker\Models\Record::setLastReviewed($record);
			\App\Modules\ModTracker\Models\Record::unsetReviewed($record, $userId, $result);
		}
	}
}
