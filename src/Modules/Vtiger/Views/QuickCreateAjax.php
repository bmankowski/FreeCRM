<?php

namespace App\Modules\Vtiger\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use App\Http\Vtiger_Request;

class QuickCreateAjax extends \App\Modules\Vtiger\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		if (!(\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CreateView'))) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		$moduleModel = $recordModel->getModule();

		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach ($requestFieldList as $fieldName => $fieldValue) {
			$fieldModel = $fieldList[$fieldName];
			if ($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBValue($fieldValue));
			}
		}

		$recordStructureInstance = \App\Modules\Vtiger\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Vtiger\Models\RecordStructure::RECORD_STRUCTURE_MODE_QUICKCREATE);
		$picklistDependencyDatasource = \App\Modules\PickList\DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer = $this->getViewer($request);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', \App\Json::encode($picklistDependencyDatasource));
		$recordStructure = $recordStructureInstance->getStructure();
		$mappingRelatedField = \App\ModuleHierarchy::getRelationFieldByHierarchy($moduleName);

		$fieldValues = [];
		$sourceRelatedField = $moduleModel->getValuesFromSource($request);
		foreach ($sourceRelatedField as $fieldName => &$fieldValue) {
			if (isset($recordStructure[$fieldName])) {
				$fieldvalue = $recordStructure[$fieldName]->get('fieldvalue');
				if (empty($fieldvalue)) {
					$recordStructure[$fieldName]->set('fieldvalue', $fieldValue);
				}
			} else {
				if (isset($fieldList[$fieldName])) {
					$fieldModel = $fieldList[$fieldName];
					$fieldModel->set('fieldvalue', $fieldValue);
					$fieldValues[$fieldName] = $fieldModel;
				}
			}
		}
		$viewer->assign('QUICKCREATE_LINKS', \App\Modules\Vtiger\Models\Link::getAllByType($moduleModel->getId(), ['QUICKCREATE_VIEW_HEADER']));
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Json::encode($mappingRelatedField));
		$viewer->assign('SOURCE_RELATED_FIELD', $fieldValues);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SINGLE_MODULE', 'SINGLE_' . $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('MODE', 'edit');
		$viewer->assign('SCRIPTS', $this->getFooterScripts($request));

		$viewer->assign('MAX_UPLOAD_LIMIT_MB', \App\Modules\Vtiger\Util::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		echo $viewer->view('QuickCreate.tpl', $moduleName, true);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{

		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.$moduleName.resources.Edit"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
