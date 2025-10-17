<?php

namespace App\Modules\Settings\WidgetsManagement\Actions;
use App\Modules\Settings\WidgetsManagement\Models\Module as Settings_WidgetsManagement_Module_Model;



/**
 * Action to save dashboard
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Dashboard extends \App\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
	}

	public function save(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Settings\WidgetsManagement\Models\Module::saveDashboard($request->get('dashboardId'), $request->get('name'));
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	public function delete(\App\Http\Vtiger_Request $request)
	{
		$dashboardId = $request->get('dashboardId');
		if($dashboardId === \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDashboard()) {
			throw new \Exception\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
		\App\Modules\Settings\WidgetsManagement\Models\Module::deleteDashboard($dashboardId);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
