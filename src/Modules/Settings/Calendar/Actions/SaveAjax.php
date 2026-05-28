<?php

namespace App\Modules\Settings\Calendar\Actions;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class SaveAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('UpdateModuleColor');
		$this->exposeMethod('UpdateModuleActiveType');
		$this->exposeMethod('UpdateCalendarConfig');
		$this->exposeMethod('updateNotWorkingDays');
		$this->exposeMethod('generateColor');
	}

	public function generateColor(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$color = \App\Modules\Settings\Calendar\Models\Module::generateColor();
		$params['color'] = $color;
		if (isset($params['viewtypesid']) && $params['viewtypesid']) {
			\App\Modules\Settings\Calendar\Models\Module::updateModuleColor($params);
		} else {
			\App\Modules\Settings\Calendar\Models\Module::updateCalendarConfig($params);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'color' => $color,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_GENERATED_COLOR', $request->getModule(false))
		));
		$response->emit();
	}

	public function UpdateModuleColor(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\App\Modules\Settings\Calendar\Models\Module::updateModuleColor($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_COLOR', $request->getModule(false))
		));
		$response->emit();
	}

	public function UpdateModuleActiveType(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\App\Modules\Settings\Calendar\Models\Module::updateModuleActiveType($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_ACTIVE_TYPE', $request->getModule(false))
		));
		$response->emit();
	}

	public function UpdateCalendarConfig(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\App\Modules\Settings\Calendar\Models\Module::updateCalendarConfig($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_CHANGES', $request->getModule(false))
		));
		$response->emit();
	}

	public function updateNotWorkingDays(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		\App\Modules\Settings\Calendar\Models\Module::updateNotWorkingDays($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_ACTIVE_TYPE', $request->getModule(false))
		));
		$response->emit();
	}
}
