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


class Export  extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($request->getModule(), 'Export')) {
			throw new \App\Exceptions\NoPermitted(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED'));
		}
	}

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		return \App\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT', $moduleName);
	}


	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);

		$sourceModule = $request->getModule();
		$viewId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		$page = $request->get('page');

		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('VIEWID', $viewId);
		$viewer->assign('PAGE', $page);
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('MODULE', 'Export');
		$viewer->assign('XML_TPL_LIST', \App\Modules\Import\Models\Module::getListTplForXmlType($sourceModule));
		$viewer->assign('EXPORT_TYPE', ['LBL_XML' => 'xml', 'LBL_CSV' => 'csv']);
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$viewer->assign('OPERATOR', $operator);
			$viewer->assign('ALPHABET_VALUE', $searchValue);
			$viewer->assign('SEARCH_KEY', $searchKey);
		}
		$viewer->assign('SEARCH_PARAMS', $request->get('search_params'));
		$viewer->view('Export.tpl', $sourceModule);
	}
}
