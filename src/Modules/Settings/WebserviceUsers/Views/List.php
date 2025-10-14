<?php

namespace FreeCRM\Modules\Settings\WebserviceUsers\Views;



/**
 * WebserviceUsers List View Class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Modules\Vtiger\Models\ListView as Vtiger_ListView_Model;
class List extends \FreeCRM\Modules\Settings\Vtiger\Views\List
{

	/**
	 * Initiate data values for listview
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @param FreeCRM_Viewer $viewer
	 */
	public function initializeListViewContents(\FreeCRM\Http\Vtiger_Request $request, FreeCRM_Viewer $viewer)
	{
		$qualifiedModuleName = $request->getModule(false);
		if (!$request->has('typeApi')) {
			$request->set('typeApi', current(\FreeCRM\Modules\Settings\WebserviceApps\Models\Module::getTypes()));
		}
		$typeApi = $request->get('typeApi');
		$this->listViewModel = Settings_Vtiger_ListView_Model::getInstance($qualifiedModuleName);
		$this->listViewModel->getModule()->typeApi = $typeApi;
		parent::initializeListViewContents($request, $viewer);
		$viewer->assign('TYPE_API', $typeApi);
	}
}
