<?php

namespace App\Modules\Settings\CustomView\Actions;



/**
 * CustomView save class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('delete');
		$this->exposeMethod('updateField');
		$this->exposeMethod('upadteSequences');
		$this->exposeMethod('setFilterPermissions');
	}

	public function delete(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		\App\Modules\Settings\CustomView\Models\Module::delete($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $saveResp['success'],
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('Delete CustomView', $request->getModule(false))
		));
		$response->emit();
	}

	public function updateField(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		\App\Modules\Settings\CustomView\Models\Module::updateField($params);
		\App\Modules\Settings\CustomView\Models\Module::updateOrderAndSort($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('Saving CustomView', $request->getModule(false))
		]);
		$response->emit();
	}

	public function upadteSequences(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$result = \App\Modules\Settings\CustomView\Models\Module::upadteSequences($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_SEQUENCES', $request->getModule(false))
		]);
		$response->emit();
	}

	public function setFilterPermissions(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$type = $request->get('type');
		if ($type == 'default') {
			$result = \App\Modules\Settings\CustomView\Models\Module::setDefaultUsersFilterView($params['tabid'], $params['cvid'], $params['user'], $params['action']);
		} elseif ($type == 'featured') {
			$result = \App\Modules\Settings\CustomView\Models\Module::setFeaturedFilterView($params['cvid'], $params['user'], $params['action']);
		}

		if (!empty($result)) {
			$data = [
				'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_EXISTS_PERMISSION_IN_CONFIG', $request->getModule(false), \App\Runtime\Vtiger_Language_Handler::translate($result, $params['tabid'])),
				'success' => false
			];
		} else {
			$data = [
				'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CONFIG', $request->getModule(false)),
				'success' => true
			];
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($data);
		$response->emit();
	}
}
