<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\Vtiger\Models;

class TransferOwnership extends \App\Runtime\BaseModel
{

	protected $skipModules = [];

	public function getSkipModules()
	{
		return $this->skipModules;
	}

	public function getRelatedModuleRecordIds(Vtiger_Request $request, $recordIds = [], $relModData)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$basicModule = $request->getModule();
		$parentModuleModel = \App\Modules\Vtiger\Models\Module::getInstance($basicModule);
		$relatedIds = [];
		$relModData = explode('::', $relModData);
		$relatedModule = $relModData[0];
		$type = $relModData[1];
		switch ($type) {
			case 0:

				$field = $relModData[2];
				foreach ($recordIds as $recordId) {
					$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, $basicModule);
					if ($recordModel->get($field) != 0 && \vtlib\Functions::getCRMRecordType($recordModel->get($field)) == $relatedModule) {
						$relatedIds[] = $recordModel->get($field);
					}
				}

				break;
			case 1:

				$relatedModuleModel = \App\Modules\Vtiger\Models\Module::getInstance($relatedModule);
				$instance = \App\CRMEntity::getInstance($relatedModule);
				$relationModel = \App\Modules\Vtiger\Models\Relation::getInstance($parentModuleModel, $relatedModuleModel);
				$fieldModel = $relationModel->getRelationField();
				$tablename = $fieldModel->get('table');
				$tabIndex = $instance->table_index;
				$relIndex = $this->getRelatedColumnName($relatedModule, $basicModule);

				if (!$relIndex) {
					break;
				}
				$sql = "SELECT vtiger_crmentity.crmid FROM vtiger_crmentity INNER JOIN $tablename ON $tablename.$tabIndex = vtiger_crmentity.crmid
						WHERE $tablename.$relIndex IN (" . $db->generateQuestionMarks($recordIds) . ")";
				$result = $db->pquery($sql, $recordIds);
				while ($crmid = $db->getSingleValue($result)) {
					$relatedIds[] = $crmid;
				}

				break;
			case 2:
				foreach ($recordIds as $recordId) {
					$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, $basicModule);
					$relationListView = \App\Modules\Vtiger\Models\RelationListView::getInstance($recordModel, $relatedModule);
					$relatedIds = $relationListView->getRelationQuery()->select(['vtiger_crmentity.crmid'])
						->distinct()
						->column();
				}
				break;
		}
		return array_unique($relatedIds);
	}

	public function transferRecordsOwnership($module, $transferOwnerId, $relatedModuleRecordIds)
	{
		$db = \App\Db::getInstance();
		$oldOwners = \vtlib\Functions::getCRMRecordMetadata($relatedModuleRecordIds);
		$currentUser = vglobal('current_user');
		$db->createCommand()->update('vtiger_crmentity', [
			'smownerid' => $transferOwnerId,
			'modifiedby' => $currentUser->id,
			'modifiedtime' => date('Y-m-d H:i:s'),
			], ['crmid' => $relatedModuleRecordIds]
		)->execute();
		$flag = \App\Modules\ModTracker\ModTracker::isTrackingEnabledForModule($module);
		if ($flag) {
			foreach ($relatedModuleRecordIds as $record) {
				$db->createCommand()->insert('vtiger_modtracker_basic', [
					'crmid' => $record,
					'module' => $module,
					'whodid' => $currentUser->id,
					'changedon' => date('Y-m-d H:i:s', time())
				])->execute();
				$id = $db->getLastInsertID('vtiger_modtracker_basic_id_seq');
				$db->createCommand()->insert('vtiger_modtracker_detail', [
					'id' => $id,
					'fieldname' => 'assigned_user_id',
					'postvalue' => $transferOwnerId,
					'prevalue' => $oldOwners[$record]['smownerid']
				])->execute();
			}
		}
	}

	public static function getInstance($module)
	{
		$instance = \App\Runtime\Vtiger_Cache::get('transferOwnership', $module);
		if (!$instance) {
			$modelClassName = \App\Loader::getComponentClassName('Model', 'TransferOwnership', $module);
			$instance = new $modelClassName();
			$instance->set('module', $module);
			\App\Runtime\Vtiger_Cache::set('transferOwnership', $module, $instance);
		}
		return $instance;
	}

	public function getRelationsByFields($privileges = true)
	{
		$module = $this->get('module');
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($module);
		$relatedModelFields = $moduleModel->getFields();

		$relatedModules = [];
		foreach ($relatedModelFields as $fieldName => $fieldModel) {
			if ($fieldModel->isReferenceField()) {
				$referenceList = $fieldModel->getReferenceList();
				foreach ($referenceList as $relation) {
					if (\App\Modules\Users\Models\Privileges::isPermitted($relation, 'EditView')) {
						$relatedModules[] = ['name' => $relation, 'field' => $fieldName];
					}
				}
			}
		}
		return $relatedModules;
	}

	public function getRelationsByRelatedList($privileges = true)
	{
		$module = $this->get('module');
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($module);
		$relatedModelFields = $moduleModel->getFields();

		$relatedModules = [];
		$relations = $moduleModel->getRelations();
		foreach ($relations as $relation) {
			$relationModule = $relation->getRelationModuleName();
			if (\App\Modules\Users\Models\Privileges::isPermitted($relationModule, 'EditView')) {
				$relatedModules[] = [
					'name' => $relationModule,
					'type' => $relation->getRelationType(),
				];
			}
		}
		return $relatedModules;
	}

	public function getRelatedColumnName($relatedModule, $findModule)
	{
		$relatedModuleModel = \App\Modules\Vtiger\Models\Module::getInstance($relatedModule);
		$relatedModelFields = $relatedModuleModel->getFields();
		foreach ($relatedModelFields as $fieldName => $fieldModel) {
			if ($fieldModel->isReferenceField()) {
				$referenceList = $fieldModel->getReferenceList();
				if (in_array($findModule, $referenceList)) {
					return $fieldModel->get('column');
				}
			}
		}
		return false;
	}
}
