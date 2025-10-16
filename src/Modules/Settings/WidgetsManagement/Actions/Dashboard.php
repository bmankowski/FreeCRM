<?php

namespace FreeCRM\Modules\Settings\WidgetsManagement\Actions;
use FreeCRM\Modules\Settings\WidgetsManagement\Models\Module as Settings_WidgetsManagement_Module_Model;



/**
 * Action to save dashboard
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Dashboard extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
	}

	public function save(\FreeCRM\Http\Vtiger_Request $request)
	{
		\FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::saveDashboard($request->get('dashboardId'), $request->get('name'));
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	public function delete(\FreeCRM\Http\Vtiger_Request $request)
	{
		$dashboardId = $request->get('dashboardId');
		if($dashboardId === \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDashboard()) {
			throw new \Exception\AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
		\FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::deleteDashboard($dashboardId);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
