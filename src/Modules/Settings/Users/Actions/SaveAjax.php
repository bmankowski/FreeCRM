<?php

namespace FreeCRM\Modules\Settings\Users\Actions;



/**
 * Basic Users Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Modules\Users\Models\Module as Users_Module_Model;
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Save
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('updateConfig');
		$this->exposeMethod('saveSwitchUsers');
		$this->exposeMethod('saveLocks');
	}

	public function updateConfig(\FreeCRM\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$recordModel = Settings_Users_Module_Model::getInstance();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $recordModel->setConfig($param),
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false))
		));
		$response->emit();
	}

	public function saveSwitchUsers(\FreeCRM\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$moduleModel = Settings_Users_Module_Model::getInstance();
		$moduleModel->saveSwitchUsers($param);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false))
		));
		$response->emit();
	}

	public function saveLocks(\FreeCRM\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$moduleModel = Settings_Users_Module_Model::getInstance();
		$moduleModel->saveLocks($param);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false))
		));
		$response->emit();
	}
}
