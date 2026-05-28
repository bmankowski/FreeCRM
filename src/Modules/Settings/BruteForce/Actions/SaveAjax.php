<?php

namespace App\Modules\Settings\BruteForce\Actions;



/**
 * Brute force save action class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author YetiForce.com
 */

class SaveAjax extends \App\Modules\Settings\Base\Views\Index
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('saveConfig');
		$this->exposeMethod('unBlock');
	}

	/**
	 * Function updates module configuration 
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function saveConfig(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$data = $request->get('param');
		\App\Modules\Settings\BruteForce\Models\Module::updateConfig($data);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_SUCCESS', $moduleName)]);
		$response->emit();
	}

	/**
	 * Function unblocks user
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function unBlock(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$id = $request->get('param');
		$status = \App\Modules\Settings\BruteForce\Models\Module::unBlock($id);

		if (!$status) {
			$return = ['success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_UNBLOCK_FAIL', $moduleName)];
		} else {
			$return = ['success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_UNBLOCK_SUCCESS', $moduleName)];
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($return);
		$response->emit();
	}
}
