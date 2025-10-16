<?php

namespace FreeCRM\Modules\Assets\Views;

/**
 * EditFieldByModal View Class for Assets
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;

use FreeCRM\Modules\Vtiger\Models\DetailView as Vtiger_DetailView_Model;
class EditFieldByModal extends \Vtiger_Index_View
{

	public function getSize(\FreeCRM\Http\Vtiger_Request $request)
	{
		return 'modal-fullscreen';
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$ID = $request->get('record');

		$recordModel = Vtiger_DetailView_Model::getInstance($moduleName, $ID)->getRecord();
		$recordStrucure = \FreeCRM\Modules\Vtiger\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \FreeCRM\Modules\Vtiger\Models\RecordStructure::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();
		$fields = [];
		foreach ($structuredValues as $fildsInBlock) {
			$fields = array_merge($fields, $fildsInBlock);
		}
		$showFields = array_keys($recordModel->getModule()->getQuickCreateFields());
		$configureFields = \FreeCRM\AppConfig::module($moduleName, 'SHOW_FIELD_IN_MODAL');
		if($configureFields){
			$showFields = array_merge($showFields, $configureFields);
		}

		$relationData = \FreeCRM\AppConfig::module($moduleName, 'SHOW_RELATION_IN_MODAL');
		$relationsModules = [];
		$relationModels = [];
		if ($relationData) {
			$relatedModuleBasicName = $relationData['module'];
			$relationsModuleName = $relationData['relatedModule'];
			$relatedRecord = $recordModel->get($relationData['relationField']);
			$metaData = \vtlib\Functions::getCRMRecordMetadata($relatedRecord);
			if ($relatedRecord && $metaData && $metaData['setype'] == $relatedModuleBasicName && $metaData['deleted'] == 0 && \FreeCRM\Modules\Users\Models\Privileges::isPermitted($relatedModuleBasicName, 'DetailView', $relatedRecord)) {
				$relatedModuleBasic = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($relatedModuleBasicName);
				foreach ($relationsModuleName as $relationModuleName) {
					$relatedModuleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($relationModuleName);
					$relationModels[$relationModuleName] = \FreeCRM\Modules\Vtiger\Models\Relation::getInstance($relatedModuleBasic, $relatedModuleModel);
					if (!empty($relationModels[$relationModuleName])) {
						$relationsModules[] = $relationModuleName;
					}
				}
			}
		}
		$hierarchy = \FreeCRM\AppConfig::module($moduleName, 'SHOW_HIERARCHY_IN_MODAL');
		$hierarchyId = '';
		if ($hierarchy !== false) {
			$hierarchyModuleName = 'Accounts';
			foreach ($fields as $fieldName => $fieldModel) {
				if ($fieldModel->isReferenceField() && in_array($hierarchyModuleName, $fieldModel->getReferenceList())) {
					$hierarchyId = $recordModel->has($fieldModel->getName()) ? $recordModel->get($fieldModel->getName()) : '';
				}
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('FIELD_LIST', $fields);
		$viewer->assign('SHOW_FIELDS', $showFields);
		$viewer->assign('RESTRICTS_ITEM', $this->getRestrictItems());
		$viewer->assign('RELATED_RECORD', $relatedRecord);
		$viewer->assign('RELATED_RECORD_METADATA', $metaData);
		$viewer->assign('RELATED_MODULE_BASIC', $relatedModuleBasicName);
		$viewer->assign('RELATED_MODULE', $relationsModules);
		$viewer->assign('RELATED_EXISTS', $relationsModules ? true : false);
		$viewer->assign('HIERARCHY_ID', $hierarchyId);
		$viewer->assign('HIERARCHY_FIELD', $hierarchy);
		$this->preProcess($request);
		$viewer->view('EditFieldByModal.tpl', $moduleName);
		$this->postProcess($request);
	}
}
