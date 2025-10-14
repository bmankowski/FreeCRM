<?php

namespace FreeCRM\Modules\Settings\BruteForce\Actions;



/**
 * Brute force save action class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author YetiForce.com
 */

use FreeCRM\Modules\Settings\BruteForce\Models\Module as Settings_BruteForce_Module_Model;
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
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
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function saveConfig(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$data = $request->get('param');
		Settings_BruteForce_Module_Model::updateConfig($data);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['message' => vtranslate('LBL_SAVE_SUCCESS', $moduleName)]);
		$response->emit();
	}

	/**
	 * Function unblocks user
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function unBlock(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$id = $request->get('param');
		$status = Settings_BruteForce_Module_Model::unBlock($id);

		if (!$status) {
			$return = ['success' => false, 'message' => vtranslate('LBL_UNBLOCK_FAIL', $moduleName)];
		} else {
			$return = ['success' => true, 'message' => vtranslate('LBL_UNBLOCK_SUCCESS', $moduleName)];
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($return);
		$response->emit();
	}
}
