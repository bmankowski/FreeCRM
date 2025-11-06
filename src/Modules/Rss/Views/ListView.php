<?php

namespace App\Modules\Rss\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


class ListView  extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Prepare Rss list view data
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('MODULE_MODEL', $moduleModel);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		// Data already assigned in preProcess, just render
		$viewer->view('ListView.tpl', $moduleName);
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		// MainLayout.tpl handles footer rendering, no separate postProcess template needed
		parent::postProcess($request);
	}
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$module = $request->getModule();
		$recordId = $request->get('id');
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($module);
		if ($recordId) {
			/* @var \App\Modules\Rss\Models\Record $recordInstance */
			$recordInstance = \App\Modules\Rss\Models\Record::getInstanceById($recordId, $module);
		} else {
			/* @var \App\Modules\Rss\Models\Record $recordInstance */
			$recordInstance = \App\Modules\Rss\Models\Record::getCleanInstance($module);
			$recordInstance->getDefaultRss();
			$recordInstance = \App\Modules\Rss\Models\Record::getInstanceById($recordInstance->getId(), $module);
		}

	$viewer = $this->getViewer($request);
	$viewer->assign('MODULE', $module);
	$viewer->assign('RECORD', $recordInstance);
	$linkParams = array('MODULE' => $module, 'ACTION' => $request->get('view'));
	$linkModels = $moduleModel->getSideBarLinks($linkParams);
	
	// Process sidebar links to determine active link
	$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);
	
	$viewer->assign('QUICK_LINKS', $linkModels);
	$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);
		$viewer->assign('LISTVIEW_HEADERS', $this->getListViewRssHeaders($module));
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Base.resources.ListView',
			"modules.$moduleName.resources.ListView",
			'modules.CustomView.resources.CustomView',
			"modules.$moduleName.resources.CustomView",
			'modules.Base.resources.CkEditor'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the list view header
	 * @return <Array> - List of \App\Modules\Base\Models\Field instances
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
			$fieldModel = new \App\Modules\Base\Models\Field();
			foreach ($fieldDetails as $name => $value) {
				$fieldModel->set($name, $value);
			}
			$fieldModel->setModule($module);
			$fieldModelsList[$fieldName] = $fieldModel;
		}
		return $fieldModelsList;
	}
}
