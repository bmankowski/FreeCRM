<?php

namespace App\Modules\Settings\Vtiger\Actions;



/**
 * System warnings basic action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class SystemWarnings extends \App\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('update');
		$this->exposeMethod('cancel');
	}

	/**
	 * Update ignore status
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function update(\App\Http\Vtiger_Request $request)
	{
		$className = $request->get('id');
		if (!class_exists($className)) {
			$result = false;
		}
		$instace = new $className;
		$result = $instace->update($request->get('params'));

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Update ignore status
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function cancel(\App\Http\Vtiger_Request $request)
	{
		\Vtiger_Session::set('SystemWarnings', true);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
