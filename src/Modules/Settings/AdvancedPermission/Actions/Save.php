<?php

namespace App\Modules\Settings\AdvancedPermission\Actions;



/**
 * Advanced permission save action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Save extends \App\Modules\Settings\Vtiger\Actions\Save
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('step1');
		$this->exposeMethod('step2');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
		}
	}

	/**
	 * Save first step
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function step1(\App\Http\Vtiger_Request $request)
	{
		if ($request->isEmpty('record') === false) {
			$recordModel = \App\Modules\Settings\AdvancedPermission\Models\Record::getInstance($request->get('record'));
		} else {
			$recordModel = new \App\Modules\Settings\AdvancedPermission\Models\Record();
		}
		$recordModel->set('name', $request->get('name'));
		$recordModel->set('tabid', $request->get('tabid'));
		$recordModel->set('action', $request->get('actions'));
		$recordModel->set('status', $request->get('status'));
		$recordModel->set('members', $request->get('members'));
		$recordModel->set('priority', $request->get('priority'));
		$recordModel->save();

		header("Location: {$recordModel->getEditViewUrl(2)}");
	}

	/**
	 * Save second step
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function step2(\App\Http\Vtiger_Request $request)
	{
		$recordModel = \App\Modules\Settings\AdvancedPermission\Models\Record::getInstance($request->get('record'));
		$conditions = \Vtiger_AdvancedFilter_Helper::transformToSave($request->get('conditions'));
		$recordModel->set('conditions', $conditions);
		$recordModel->save();

		header("Location: {$recordModel->getDetailViewUrl()}");
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
