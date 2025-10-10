<?php

namespace FreeCRM\Modules\Calendar\Views;

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
class QuickCreateAjax extends \Vtiger_Index_View
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		$moduleList = ['Calendar', 'Events'];

		$quickCreateContents = [];
		foreach ($moduleList as $module) {
			$info = [];

			$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($module);
			$moduleModel = $recordModel->getModule();

			$fieldList = $moduleModel->getFields();
			$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

			foreach ($requestFieldList as $fieldName => $fieldValue) {
				$fieldModel = $fieldList[$fieldName];
				if ($fieldModel->isEditable()) {
					$recordModel->set($fieldName, $fieldModel->getDBValue($fieldValue));
				}
			}

			$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_QUICKCREATE);
			$recordStructure = $recordStructureInstance->getStructure();
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

			$info['recordStructureModel'] = $recordStructureInstance;
			$info['recordStructure'] = $recordStructure;
			$info['moduleModel'] = $moduleModel;
			$quickCreateContents[$module] = $info;
		}
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer = $this->getViewer($request);
		$viewer->assign('QUICKCREATE_LINKS', \FreeCRM\Modules\Vtiger\Models\Link::getAllByType($moduleModel->getId(), ['QUICKCREATE_VIEW_HEADER']));
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', \App\Json::encode($picklistDependencyDatasource));
		$mappingRelatedField = \App\ModuleHierarchy::getRelationFieldByHierarchy($moduleName);
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Json::encode($mappingRelatedField));
		$viewer->assign('SOURCE_RELATED_FIELD', $fieldValues);
		$viewer->assign('THREEDAYSAGO', date('Y-n-j', strtotime('-3 day')));
		$viewer->assign('TWODAYSAGO', date('Y-n-j', strtotime('-2 day')));
		$viewer->assign('ONEDAYAGO', date('Y-n-j', strtotime('yesterday')));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('ONEDAYLATER', date('Y-n-j', strtotime('tomorrow')));
		$viewer->assign('TWODAYLATER', date('Y-n-j', strtotime('+2 day')));
		$viewer->assign('THREEDAYSLATER', date('Y-n-j', strtotime('+3 day')));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUICK_CREATE_CONTENTS', $quickCreateContents);
		$viewer->assign('USER_MODEL', \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->assign('SCRIPTS', $this->getFooterScripts($request));
		$viewer->view('QuickCreate.tpl', $moduleName);
	}
}
