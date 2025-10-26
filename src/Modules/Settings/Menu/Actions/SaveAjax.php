<?php

namespace App\Modules\Settings\Menu\Actions;


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
		$this->exposeMethod('createMenu');
		$this->exposeMethod('updateMenu');
		$this->exposeMethod('removeMenu');
		$this->exposeMethod('updateSequence');
		$this->exposeMethod('copyMenu');
	}

	public function createMenu(\App\Http\Vtiger_Request $request)
	{
		$data = $request->get('mdata');
		$recordModel = \App\Modules\Settings\Menu\Models\Record::getCleanInstance();
		$recordModel->initialize($data);
		$recordModel->save();
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_ITEM_ADDED_TO_MENU', $request->getModule(false))
		));
		$response->emit();
	}

	public function updateMenu(\App\Http\Vtiger_Request $request)
	{
		$data = $request->get('mdata');
		$recordModel = \App\Modules\Settings\Menu\Models\Record::getInstanceById($data['id']);
		$recordModel->initialize($data);
		$recordModel->set('edit', true);
		$recordModel->save($data);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_MENU', $request->getModule(false))
		));
		$response->emit();
	}

	public function removeMenu(\App\Http\Vtiger_Request $request)
	{
		$data = $request->get('mdata');
		$settingsModel = \App\Modules\Settings\Menu\Models\Record::getCleanInstance();
		$settingsModel->removeMenu($data);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_REMOVED_MENU_ITEM', $request->getModule(false))
		));
		$response->emit();
	}

	public function updateSequence(\App\Http\Vtiger_Request $request)
	{
		$data = $request->get('mdata');
		$recordModel = \App\Modules\Settings\Menu\Models\Record::getCleanInstance();
		$recordModel->saveSequence($data, true);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_MAP_MENU', $request->getModule(false))
		));
		$response->emit();
	}
	
	/**
	 * Function to trigger copying menu
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function copyMenu(\App\Http\Vtiger_Request $request)
	{
		$fromRole = filter_var($request->get('fromRole'), FILTER_SANITIZE_NUMBER_INT);
		$toRole = filter_var($request->get('toRole'), FILTER_SANITIZE_NUMBER_INT);
		$recordModel = \App\Modules\Settings\Menu\Models\Record::getCleanInstance();
		$recordModel->copyMenu($fromRole, $toRole);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_MAP_MENU', $request->getModule(false))
		));

		$response->emit();
	}
}
