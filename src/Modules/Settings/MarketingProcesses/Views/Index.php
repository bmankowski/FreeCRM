<?php

namespace App\Modules\Settings\MarketingProcesses\Views;


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
		
		\App\Log::trace('Start ' . __METHOD__);
		$qualifiedModule = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\MarketingProcesses\Models\Module::getCleanInstance();
		$currentUser = $request->getUser();

		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->assign('USER_MODEL', $currentUser);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('LEADS_MODULE_MODEL', \App\Modules\Base\Models\Module::getInstance('Leads'));
		$viewer->assign('ACCOUNTS_MODULE_MODEL', \App\Modules\Base\Models\Module::getInstance('Accounts'));
		
		// Prepare MarketingProcesses-specific data for IndexContent template
		$this->prepareMarketingProcessesData($viewer);
		
		if ($request->isAjax()) {
			// AJAX request - return content only
			$viewer->view('IndexContent.tpl', $qualifiedModule);
		} else {
			// Initial page load - return full page with MainLayout
			$viewer->view('Index.tpl', $qualifiedModule);
		}
		\App\Log::trace('End ' . __METHOD__);
	}
	
	/**
	 * Prepare data for MarketingProcesses IndexContent template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareMarketingProcessesData($viewer)
	{
		$moduleModel = $viewer->getTemplateVars('MODULE_MODEL');
		$conversion = $moduleModel->getConfig('conversion');
		
		// Decode mapping JSON
		$mapping = [];
		if (!empty($conversion['mapping'])) {
			$mapping = \App\Json::decode($conversion['mapping']);
		}
		$viewer->assign('CONVERSION_MAPPING', $mapping);
		
		// Prepare accessible groups for Leads
		$viewer->assign('ALL_ACTIVEGROUP_LIST', \App\Fields\Owner::getInstance('Leads')->getAccessibleGroups());
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.Index",
			"modules.Settings.Leads.resources.LeadMapping",
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
