<?php

namespace FreeCRM\Modules\PriceBooks\Views;

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
class ListPriceUpdate extends View
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($moduleName)) {
			throw new \Exception\AppException(vtranslate($moduleName) . ' ' . vtranslate('LBL_NOT_ACCESSIBLE'));
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
		$moduleName = $request->getModule();
		$priceBookId = $request->get('record');
		$relId = $request->get('relid');
		$currentPrice = $request->get('currentPrice');

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('PRICEBOOK_ID', $priceBookId);
		$viewer->assign('REL_ID', $relId);
		$viewer->assign('CURRENT_PRICE', $currentPrice);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('ListPriceUpdate.tpl', $moduleName);
		parent::postProcess($request);
	}
}
