<?php

namespace App\Modules\Settings\HideBlocks\Views;
use App\Modules\Settings\HideBlocksModels\Record as Settings_HideBlocks_Record_Model;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

Class Settings_HideBlocks_Conditions_View extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$blockId = $request->get('blockid');
		$views = $request->get('views');
		$qualifiedModuleName = $request->getModule(false);
		$mode = '';
		$viewer = $this->getViewer($request);


		if ($views != '')
			$views = implode($views, ',');
		if ($recordId) {
			$mode = 'edit';
		} else {
			
		}
		$moduleModel = Settings_HideBlocks_Record_Model::getModuleInstanceByBlockId($blockId);
		$recordStrucure = \App\Modules\Vtiger\Models\RecordStructure::getInstanceForModule($moduleModel);
		$structuredValues = $recordStrucure->getStructure();
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$blockInstance = vtlib\Block::getInstance($blockId, $moduleModel);
		$blockLabel = $blockInstance->label;
		$blockModelList = $moduleModel->getBlocks();
		$blockModel = $blockModelList[$blockLabel];
		$fieldModelList = $blockModel->getFields();
		$mandatoryFields = array();
		if (!empty($fieldModelList)) {
			$values[$blockLabel] = array();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				if ($fieldModel->isMandatory()) {
					$mandatoryFields[$fieldName] = $fieldModel;
				}
			}
		}
		$viewer->assign('MANDATORY_FIELDS', $mandatoryFields);
		$viewer->assign('DATE_FILTERS', \Vtiger_AdvancedFilter_Helper::getDateFilter($qualifiedModuleName));
		$viewer->assign('ADVANCED_FILTER_OPTIONS', \App\Modules\Settings\Workflows\Models\Field::getAdvancedFilterOptions());
		$viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', \App\Modules\Settings\Workflows\Models\Field::getAdvancedFilterOpsByFieldType());
		$viewer->assign('COLUMNNAME_API', 'getName');
		$recordModel = Settings_HideBlocks_Record_Model::getInstanceById($recordId, $qualifiedModuleName);
		if ($recordModel)
			$viewer->assign('ADVANCE_CRITERIA', $this->transformToAdvancedFilterCondition($recordModel->get('conditions')));
		$viewer->assign('MODE', $mode);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('MODULE', 'HideBlocks');
		$viewer->assign('SOURCE_MODULE', $moduleModel->get('name'));
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('BLOCKID', $blockId);
		$viewer->assign('ENABLED', $request->get('enabled'));
		$viewer->assign('VIEWS', $views);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->view('Conditions.tpl', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.Conditions",
			"modules.Settings.$moduleName.resources.AdvanceFilter"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function transformToAdvancedFilterCondition($conditions)
	{
		$conditions = \App\Json::decode($conditions);
		$transformedConditions = array();
		if (!empty($conditions)) {
			foreach ($conditions as $index => $info) {
				if (!($info['groupid'])) {
					$firstGroup[] = array('columnname' => $info['fieldname'], 'comparator' => $info['operation'], 'value' => $info['value'],
						'column_condition' => $info['joincondition'], 'valuetype' => $info['valuetype'], 'groupid' => $info['groupid']);
				} else {
					$secondGroup[] = array('columnname' => $info['fieldname'], 'comparator' => $info['operation'], 'value' => $info['value'],
						'column_condition' => $info['joincondition'], 'valuetype' => $info['valuetype'], 'groupid' => $info['groupid']);
				}
			}
		}
		$transformedConditions[1] = array('columns' => $firstGroup);
		$transformedConditions[2] = array('columns' => $secondGroup);
		return $transformedConditions;
	}
}
