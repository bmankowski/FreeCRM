<?php

namespace FreeCRM\Modules\Settings\CustomView\Actions;
use FreeCRM\Modules\Settings\CustomView\Models\Module as Settings_CustomView_Module_Model;



/**
 * CustomView save class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('delete');
		$this->exposeMethod('updateField');
		$this->exposeMethod('upadteSequences');
		$this->exposeMethod('setFilterPermissions');
	}

	public function delete(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		Settings_CustomView_Module_Model::delete($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $saveResp['success'],
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('Delete CustomView', $request->getModule(false))
		));
		$response->emit();
	}

	public function updateField(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		Settings_CustomView_Module_Model::updateField($params);
		Settings_CustomView_Module_Model::updateOrderAndSort($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('Saving CustomView', $request->getModule(false))
		]);
		$response->emit();
	}

	public function upadteSequences(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$result = Settings_CustomView_Module_Model::upadteSequences($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_SEQUENCES', $request->getModule(false))
		]);
		$response->emit();
	}

	public function setFilterPermissions(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$type = $request->get('type');
		if ($type == 'default') {
			$result = Settings_CustomView_Module_Model::setDefaultUsersFilterView($params['tabid'], $params['cvid'], $params['user'], $params['action']);
		} elseif ($type == 'featured') {
			$result = Settings_CustomView_Module_Model::setFeaturedFilterView($params['cvid'], $params['user'], $params['action']);
		}

		if (!empty($result)) {
			$data = [
				'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_EXISTS_PERMISSION_IN_CONFIG', $request->getModule(false), \FreeCRM\Runtime\Vtiger_Language_Handler::translate($result, $params['tabid'])),
				'success' => false
			];
		} else {
			$data = [
				'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false)),
				'success' => true
			];
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($data);
		$response->emit();
	}
}
