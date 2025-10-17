<?php

namespace App\Modules\Settings\WebserviceUsers\Views;



/**
 * WebserviceUsers List View Class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class List extends \App\Modules\Settings\Vtiger\Views\List
{

	/**
	 * Initiate data values for listview
	 * @param \App\Http\Vtiger_Request $request
	 * @param CRM_Viewer $viewer
	 */
	public function initializeListViewContents(\App\Http\Vtiger_Request $request, CRM_Viewer $viewer)
	{
		$qualifiedModuleName = $request->getModule(false);
		if (!$request->has('typeApi')) {
			$request->set('typeApi', current(\App\Modules\Settings\WebserviceApps\Models\Module::getTypes()));
		}
		$typeApi = $request->get('typeApi');
		$this->listViewModel = \App\Modules\Settings\Vtiger\Models\ListView::getInstance($qualifiedModuleName);
		$this->listViewModel->getModule()->typeApi = $typeApi;
		parent::initializeListViewContents($request, $viewer);
		$viewer->assign('TYPE_API', $typeApi);
	}
}
