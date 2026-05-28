<?php

namespace App\Modules\Settings\WebserviceUsers\Views;



/**
 * WebserviceUsers List View Class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class ListView extends \App\Modules\Settings\Base\Views\ListView
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$qualifiedModuleName = $request->getModule(false);
		if (!$request->has('typeApi')) {
			$request->set('typeApi', current(\App\Modules\Settings\WebserviceApps\Models\Module::getTypes()));
		}
		$typeApi = $request->get('typeApi');
		$this->listViewModel = \App\Modules\Settings\Base\Models\ListView::getInstance($qualifiedModuleName);
		$this->listViewModel->getModule()->typeApi = $typeApi;
		parent::preProcess($request, $display);
		$viewer = $this->getViewer($request);
		$viewer->assign('TYPE_API', $typeApi);
		
		// Prepare WebserviceUsers-specific data for ListViewHeader template
		$this->prepareWebserviceUsersListViewData($viewer);
	}
	
	/**
	 * Prepare data for WebserviceUsers ListViewHeader template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareWebserviceUsersListViewData($viewer)
	{
		$viewer->assign('WEBSERVICE_TYPES', \App\Modules\Settings\WebserviceApps\Models\Module::getTypes());
	}
}
