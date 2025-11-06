<?php

namespace App\Modules\Documents\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class Detail  extends \App\Modules\Base\Views\Detail
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showDocumentRelations');
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Assign Documents-specific data
		$recordId = $request->get('record');
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId);
		$fileType = $recordModel->get('filetype');
		$fileIcon = \App\Layout\Icon::getIconByFileType($fileType);

		$viewer = $this->getViewer($request);
		$viewer->assign('NO_SUMMARY', true);
		$viewer->assign('EXTENSION_ICON', $fileIcon);
	}

	/**
	 * Function to get Ajax is enabled or not
	 * @param \App\Modules\Base\Models\Record record model
	 * @return <boolean> true/false
	 */
	public function isAjaxEnabled($recordModel)
	{
		return false;
	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	public function showModuleBasicView(\App\Http\Vtiger_Request $request)
	{
		return $this->showModuleDetailView($request);
	}

	public function showDocumentRelations(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$data = \App\Modules\Documents\Models\Record::getReferenceModuleByDocId($recordId);
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORDID', $recordId);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('LIMIT', 'no_limit');
		$viewer->assign('DATA', $data);

		echo $viewer->view('DetailViewDocumentRelations.tpl', $moduleName, true);
	}
}
