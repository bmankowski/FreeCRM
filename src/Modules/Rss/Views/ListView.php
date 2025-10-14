<?php

namespace FreeCRM\Modules\Rss\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class ListView extends \Vtiger_Index_View
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
	}

	public function preProcessTplName(\FreeCRM\Http\Vtiger_Request $request)
	{
		return 'ListViewPreProcess.tpl';
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->view('ListViewContents.tpl', $moduleName);
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$viewer->view('ListViewPostProcess.tpl', $moduleName);
		parent::postProcess($request);
	}
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */

	public function initializeListViewContents(\FreeCRM\Http\Vtiger_Request $request, FreeCRM_Viewer $viewer)
	{
		$module = $request->getModule();
		$recordId = $request->get('id');
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($module);
		if ($recordId) {
			$recordInstance = \FreeCRM\Modules\Rss\Models\Record::getInstanceById($recordId, $module);
		} else {
			$recordInstance = \FreeCRM\Modules\Rss\Models\Record::getCleanInstance($module);
			$recordInstance->getDefaultRss();
			$recordInstance = \FreeCRM\Modules\Rss\Models\Record::getInstanceById($recordInstance->getId(), $module);
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $module);
		$viewer->assign('RECORD', $recordInstance);
		$linkParams = array('MODULE' => $module, 'ACTION' => $request->get('view'));
		$viewer->assign('QUICK_LINKS', $moduleModel->getSideBarLinks($linkParams));
		$viewer->assign('LISTVIEW_HEADERS', $this->getListViewRssHeaders($module));
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of \FreeCRM\Modules\Vtiger\Models\JsScript instances
	 */
	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.List',
			"modules.$moduleName.resources.List",
			'modules.CustomView.resources.CustomView',
			"modules.$moduleName.resources.CustomView",
			'modules.Vtiger.resources.CkEditor'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the list view header
	 * @return <Array> - List of \FreeCRM\Modules\Vtiger\Models\Field instances
	 */
	public function getListViewRssHeaders($module)
	{
		$headerFields = array(
			'title' => array(
				'uitype' => '1',
				'name' => 'title',
				'label' => 'LBL_SUBJECT',
				'typeofdata' => 'V~O',
				'diplaytype' => '1',
			),
			'sender' => array(
				'uitype' => '1',
				'name' => 'sender',
				'label' => 'LBL_SENDER',
				'typeofdata' => 'V~O',
				'diplaytype' => '1',
			)
		);
		foreach ($headerFields as $fieldName => $fieldDetails) {
			$fieldModel = new \FreeCRM\Modules\Vtiger\Models\Field();
			foreach ($fieldDetails as $name => $value) {
				$fieldModel->set($name, $value);
			}
			$fieldModel->module = $module;
			$fieldModelsList[$fieldName] = $fieldModel;
		}
		return $fieldModelsList;
	}
}
