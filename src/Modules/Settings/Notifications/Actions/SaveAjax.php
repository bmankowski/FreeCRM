<?php

namespace App\Modules\Settings\Notifications\Actions;



/**
 * Save notification
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \App\Modules\Settings\Vtiger\Actions\Index
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('addOrRemoveMembers');
		$this->exposeMethod('lock');
		$this->exposeMethod('exceptions');
	}

	/**
	 * Function adds/removes members
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function addOrRemoveMembers(\App\Http\Vtiger_Request $request)
	{
		$module = $request->get('srcModule');
		$members = $request->get('members');
		$state = $request->get('isToAdd') ? 1 : 0;
		if (!empty($members)) {
			if (!is_array($members)) {
				$members = [$members];
			}
			$watchdogModel = \App\Modules\Vtiger\Models\Watchdog::getInstance($module);
			foreach ($members as $member) {
				$watchdogModel->changeModuleState($state, $member);
			}
			\App\Modules\Vtiger\Models\Watchdog::reloadCache();
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	/**
	 * Function sets lock status
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function lock(\App\Http\Vtiger_Request $request)
	{
		$module = $request->get('srcModule');
		$members = $request->get('members');
		$lock = $request->get('lock');
		if (!empty($members)) {
			if (!is_array($members)) {
				$members = [$members];
			}
			$watchdogModel = \App\Modules\Vtiger\Models\Watchdog::getInstance($module);
			foreach ($members as $member) {
				$watchdogModel->lock($lock, $member);
			}
			\App\Modules\Vtiger\Models\Watchdog::reloadCache();
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	/**
	 * Function sets exceptions for users
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function exceptions(\App\Http\Vtiger_Request $request)
	{
		$module = $request->get('srcModule');
		$member = $request->get('member');
		$exceptions = $request->get('exceptions');
		if (!empty($member)) {
			$watchdogModel = \App\Modules\Vtiger\Models\Watchdog::getInstance($module);
			$watchdogModel->exceptions($exceptions, $member);
			\App\Modules\Vtiger\Models\Watchdog::reloadCache();
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
