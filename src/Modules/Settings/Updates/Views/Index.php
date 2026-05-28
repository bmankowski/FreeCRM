<?php

namespace App\Modules\Settings\Updates\Views;
use App\Modules\Settings\UpdatesModels\Module;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$updates = \App\Modules\Settings\Updates\Models\Module::getUpdates();

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$viewer->assign('UPDATES', $updates);
		$viewer->assign('MODULE', $qualifiedModuleName);
		
		// Prepare Updates-specific data for IndexContent template
		$this->prepareUpdatesData($viewer);

		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('IndexView.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for Updates IndexContent template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareUpdatesData($viewer)
	{
		$viewer->assign('USER_MODULE_IMPORT_URL', \App\Modules\Settings\ModuleManager\Models\Module::getUserModuleImportUrl());
	}
}
