<?php

namespace FreeCRM\Modules\Announcements\Actions;

/**
 * Watchdog Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BasicAjax extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function __construct()
	{
		$this->exposeMethod('mark');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();

		if ($mode) {
			$this->invokeExposedMethod($mode, $request);
		}
	}

	public function mark(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$state = $request->get('type');

		$announcements = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$announcements->setMark($record, $state);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($state);
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
