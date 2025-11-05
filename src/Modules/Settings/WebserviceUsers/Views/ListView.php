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

	/**
	 * Initiate data values for listview
	 * @param \App\Http\Vtiger_Request $request
	 * @param \App\Runtime\CRM_Viewer $viewer
	 */
	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$qualifiedModuleName = $request->getModule(false);
		if (!$request->has('typeApi')) {
			$request->set('typeApi', current(\App\Modules\Settings\WebserviceApps\Models\Module::getTypes()));
		}
		$typeApi = $request->get('typeApi');
		$this->listViewModel = \App\Modules\Settings\Base\Models\ListView::getInstance($qualifiedModuleName);
		$this->listViewModel->getModule()->typeApi = $typeApi;
		parent::initializeListViewContents($request, $viewer);
		$viewer->assign('TYPE_API', $typeApi);
	}
}
