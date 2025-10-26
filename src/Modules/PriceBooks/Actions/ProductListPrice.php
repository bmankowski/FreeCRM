<?php

namespace App\Modules\PriceBooks\Actions;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class ProductListPrice extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleModel = $request->getModule();
		$priceBookModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleModel);
		$listPrice = $priceBookModel->getProductsListPrice($request->get('itemId'));
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array($listPrice));
		$response->emit();
	}
}
