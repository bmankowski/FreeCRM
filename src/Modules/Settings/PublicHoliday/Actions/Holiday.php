<?php

namespace App\Modules\Settings\PublicHoliday\Actions;
use App\Modules\Settings\PublicHolidayModels\Module;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Holiday extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function __construct()
	{
		$this->exposeMethod('delete');
		$this->exposeMethod('save');
	}

	/**
	 * Delete date
	 * @param <Object> $request
	 * @return true if deleted, false otherwise
	 */
	public function delete(\App\Http\Vtiger_Request $request)
	{
		$response = new \App\Http\Vtiger_Response();
		$moduleName = 'Settings:' . $request->getModule();

		try {
			$id = $request->get('id');

			if (\App\Modules\Settings\PublicHoliday\Models\Module::delete($id)) {
				$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('JS_HOLIDAY_DELETE_OK', $moduleName)));
			} else {
				$response->setResult(array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('JS_HOLIDAY_DELETE_ERROR', $moduleName)));
			}
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}

		$response->emit();
	}

	/**
	 * Save date
	 * @param <Object> $request
	 * @return true if saved, false otherwise
	 */
	public function save(\App\Http\Vtiger_Request $request)
	{
		$response = new \App\Http\Vtiger_Response();
		$moduleName = 'Settings:' . $request->getModule();

		try {
			$id = $request->get('holidayId');
			$date = DateTimeField::convertToDBFormat($request->get('holidayDate'));
			$name = $request->get('holidayName');
			$type = $request->get('holidayType');

			if (empty($name) || empty($date)) {
				$response->setResult(array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_FILL_FORM_ERROR', $moduleName)));
			} else if (!empty($id)) {
				if (\App\Modules\Settings\PublicHoliday\Models\Module::edit($id, $date, $name, $type)) {
					$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_EDIT_DATE_OK', $moduleName)));
				} else {
					$response->setResult(array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_EDIT_DATE_ERROR', $moduleName)));
				}
			} else {
				if (\App\Modules\Settings\PublicHoliday\Models\Module::save($date, $name, $type)) {
					$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_NEW_DATE_OK', $moduleName)));
				} else {
					$response->setResult(array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_NEW_DATE_ERROR', $moduleName)));
				}
			}
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}

		$response->emit();
	}
}
