<?php

namespace App\Modules\Settings\Users\Actions;



/**
 * Basic Users Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class SaveAjax extends \App\Modules\Settings\Base\Actions\Save
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('updateConfig');
		$this->exposeMethod('saveSwitchUsers');
		$this->exposeMethod('saveLocks');
	}

	public function updateConfig(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$recordModel = \App\Modules\Settings\Users\Models\Module::getInstance();
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $recordModel->setConfig($param),
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false))
		));
		$response->emit();
	}

	public function saveSwitchUsers(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$moduleModel = \App\Modules\Settings\Users\Models\Module::getInstance();
		$moduleModel->saveSwitchUsers($param);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false))
		));
		$response->emit();
	}

	public function saveLocks(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$moduleModel = \App\Modules\Settings\Users\Models\Module::getInstance();
		$moduleModel->saveLocks($param);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false))
		));
		$response->emit();
	}
}
