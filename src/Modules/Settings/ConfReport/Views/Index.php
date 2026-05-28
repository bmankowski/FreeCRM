<?php

namespace App\Modules\Settings\ConfReport\Views;


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
		\App\Cache\Cache::clear();
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		
		// Prepare all data in controller instead of calling functions in template
		$this->prepareConfReportData($viewer, $qualifiedModuleName);
		
		$viewer->assign('CCURL', 'index.php?module=OSSMail&view=CheckConfig');
		$viewer->assign('MODULE', $qualifiedModuleName);
		
		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('Index.tpl', $qualifiedModuleName);
		}
	}

	/**
	 * Prepare configuration report data
	 * Moves data preparation from template to controller for better MVC separation
	 */
	protected function prepareConfReportData($viewer, $module)
	{
		$viewer->assign('CONFIGURATION_LIBRARY', 
			\App\Modules\Settings\ConfReport\Models\Module::getConfigurationLibrary());
		$viewer->assign('CONFIGURATION_VALUES', 
			\App\Modules\Settings\ConfReport\Models\Module::getConfigurationValue());
		$viewer->assign('SYSTEM_INFO', 
			\App\Modules\Settings\ConfReport\Models\Module::getSystemInfo());
		$viewer->assign('HARDWARE_INFO', 
			\App\Modules\Settings\ConfReport\Models\Module::getHardwareInfo());
		$viewer->assign('PERMISSIONS_FILES', 
			\App\Modules\Settings\ConfReport\Models\Module::getPermissionsFiles());
	}
}
