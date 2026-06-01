<?php

namespace App\Modules\Base\Views;

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

class QuickCreateAjax extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		if (!(\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CreateView'))) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
		$moduleModel = $recordModel->getModule();

		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach ($requestFieldList as $fieldName => $fieldValue) {
			$fieldModel = $fieldList[$fieldName];
			if ($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBValue($fieldValue));
			}
		}

		$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_QUICKCREATE);
		$picklistDependencyDatasource = \App\Modules\PickList\DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer = $this->getViewer($request);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', \App\Utils\Json::encode($picklistDependencyDatasource));
		$recordStructure = $recordStructureInstance->getStructure();
		$mappingRelatedField = \App\Core\ModuleHierarchy::getRelationFieldByHierarchy($moduleName);

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
		$viewer->assign('QUICKCREATE_LINKS', \App\Modules\Base\Models\Link::getAllByType($moduleModel->getId(), ['QUICKCREATE_VIEW_HEADER']));
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Utils\Json::encode($mappingRelatedField));
		$viewer->assign('SOURCE_RELATED_FIELD', $fieldValues);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SINGLE_MODULE', 'SINGLE_' . $moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('VIEW', $request->get('view'));
		if (method_exists($recordModel, 'getBaseCurrencyDetails')) {
			$baseCurrencyDetails = $recordModel->getBaseCurrencyDetails();
			if (!empty($baseCurrencyDetails['currencyid'])) {
				$viewer->assign('BASE_CURRENCY_ID', $baseCurrencyDetails['currencyid']);
				$viewer->assign('BASE_CURRENCY_NAME', 'curname' . $baseCurrencyDetails['currencyid']);
			}
			if (!empty($baseCurrencyDetails['symbol'])) {
				$viewer->assign('BASE_CURRENCY_SYMBOL', $baseCurrencyDetails['symbol']);
			}
		}
		$viewer->assign('MODE', 'edit');
		$viewer->assign('SCRIPTS', $this->getFooterScripts($request));

		$viewer->assign('MAX_UPLOAD_LIMIT_MB', \App\Modules\Base\Helpers\Util::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', \App\Core\AppConfig::main('upload_maxsize'));
		
		// Assign salutation field model if module has salutation field
		// This is needed for the Salutation.tpl template which is used for fields with uitype 'salutation'
		// Always assign the variable (even if false) to avoid Smarty warnings when template checks for it
		$salutationFieldModel = \App\Modules\Base\Models\Field::getInstance('salutationtype', $moduleModel);
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel ? $salutationFieldModel : false);

		$imageDetails = method_exists($recordModel, 'getImageDetails') ? $recordModel->getImageDetails() : [];
		$viewer->assign('IMAGE_DETAILS', is_array($imageDetails) ? $imageDetails : []);
		
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
		$request->validateReadAccess();
	}
}
