<?php

namespace App\Modules\HelpDesk\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use App\Http\Vtiger_Request;

class Detail  extends \App\Modules\Vtiger\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showRelatedRecords');
		$this->exposeMethod('showCharts');
		$this->exposeMethod('showRelatedProductsServices');
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Vtiger\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'~libraries/jquery/flot/jquery.flot.min.js',
			'~libraries/jquery/flot/jquery.flot.resize.js',
			'~libraries/jquery/flot/jquery.flot.stack.min.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function showCharts(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$viewer = $this->getViewer($request);
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance('OSSTimeControl');
		if ($moduleModel)
			$data = $moduleModel->getTimeUsers($recordId, $moduleName);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->view('charts/ShowTimeHelpDesk.tpl', $moduleName);
	}

	public function showRelatedProductsServices(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$detailViewModel = \App\Modules\Vtiger\Models\DetailView::getInstance($moduleName, $recordId);
		$recordModel = $detailViewModel->getRecord();
		$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId);
		$detailViewLinks = $detailViewModel->getDetailViewLinks($detailViewLinkParams);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORDID', $recordId);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('LIMIT', 'no_limit');
		$viewer->assign('IS_READ_ONLY', $request->getBoolean('isReadOnly'));
		
		return $viewer->view('DetailViewProductsServicesContents.tpl', $moduleName, true);
	}
}
