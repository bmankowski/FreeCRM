<?php

namespace FreeCRM\Modules\Faq\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;

use FreeCRM\Modules\PickList\DependencyPicklist as Vtiger_DependencyPicklist;
class Edit extends \Vtiger_Index_View
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if (!empty($record) && $request->getBoolean('isDuplicate') === true) {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');
		} else if (!empty($record)) {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
			$viewer->assign('RECORD_ID', $record);
			$viewer->assign('MODE', 'edit');
		} else {
			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');

			$parentId = $request->get('parentId');
			$parentModule = $request->get('parentModule');
			if ($parentId && $parentModule === 'HelpDesk') {
				$parentRecordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($parentId, $parentModule);
				$recordModel = \FreeCRM\Modules\Faq\Models\Record::getInstanceFromHelpDesk($parentRecordModel);
			}
		}

		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach ($requestFieldList as $fieldName => $fieldValue) {
			$fieldModel = $fieldList[$fieldName];
			if ($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBValue($fieldValue));
			}
		}
		$recordStructureInstance = \FreeCRM\Modules\Vtiger\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \FreeCRM\Modules\Vtiger\Models\RecordStructure::RECORD_STRUCTURE_MODE_EDIT);
		$recordStructure = $recordStructureInstance->getStructure();

		$viewMode = $request->get('view_mode');
		if (!empty($viewMode)) {
			$viewer->assign('VIEW_MODE', $viewMode);
		}

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$isRelationOperation = $request->get('relationOperation');
		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if ($isRelationOperation) {
			$sourceModule = $request->get('sourceModule');
			$sourceRecord = $request->get('sourceRecord');

			$viewer->assign('SOURCE_MODULE', $sourceModule);
			$viewer->assign('SOURCE_RECORD', $sourceRecord);
			$sourceRelatedField = $moduleModel->getValuesFromSource($request);
			foreach ($recordStructure as &$block) {
				foreach ($sourceRelatedField as $field => &$value) {
					if (isset($block[$field])) {
						$fieldvalue = $block[$field]->get('fieldvalue');
						if (empty($fieldvalue)) {
							$block[$field]->set('fieldvalue', $value);
						}
					}
				}
			}
		}
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', \App\Json::encode($picklistDependencyDatasource));
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Json::encode(\App\ModuleHierarchy::getRelationFieldByHierarchy($moduleName)));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', \FreeCRM\Modules\Vtiger\Util::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		$viewer->view('EditView.tpl', $moduleName);
	}
}
