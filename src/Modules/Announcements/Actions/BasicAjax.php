<?php

namespace App\Modules\Announcements\Actions;

/**
 * Watchdog Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BasicAjax extends \App\Runtime\BaseActionController
{

	public function __construct()
	{
		$this->exposeMethod('mark');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();

		if ($mode) {
			$this->invokeExposedMethod($mode, $request);
		}
	}

	public function mark(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$state = $request->get('type');

		$announcements = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$announcements->setMark($record, $state);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($state);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
