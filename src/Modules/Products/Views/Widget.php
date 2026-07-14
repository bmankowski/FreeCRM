<?php

namespace App\Modules\Products\Views;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */


use App\Http\Vtiger_Request;
class Widget  extends \App\Modules\Base\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showProductsServices');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode) && $this->isMethodExposed($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function showProductsServices(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$fromModule = $request->get('fromModule');
		$mod = $request->get('mod');
		$viewer = $this->getViewer($request);
		$moduleModel = \App\Modules\Products\Models\SummaryWidget::getCleanInstance();
		$moduleModel->getProductsServices($request, $viewer);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('RECORDID', $request->get('record'));
		$viewer->assign('SOURCE_MODULE', $fromModule);
		$viewer->assign('RELATED_MODULE', $mod);
		$viewer->assign('IS_ASSETS_MODULE_PERMITTED', \App\Modules\Users\Models\Privileges::isPermitted('Assets'));
		$viewer->assign('IS_ASSETS_CREATE_PERMITTED', \App\Modules\Users\Models\Privileges::isPermitted('Assets', 'CreateView'));
		$viewer->assign('IS_OSSSOLD_SERVICES_CREATE_PERMITTED', \App\Modules\Users\Models\Privileges::isPermitted('OSSSoldServices', 'CreateView'));
		$viewer->view('widgets/ProductsServices.tpl', $moduleName);
	}
}
