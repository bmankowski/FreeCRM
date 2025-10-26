<?php

namespace App\Modules\Settings\LangManagement\Actions;


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

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('addTranslation');
		$this->exposeMethod('saveTranslation');
		$this->exposeMethod('deleteTranslation');
		$this->exposeMethod('add');
		$this->exposeMethod('save');
		$this->exposeMethod('saveView');
		$this->exposeMethod('delete');
		$this->exposeMethod('setAsDefault');
	}

	public function addTranslation(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$form_data = $params['form_data'];
		$langs = json_decode($form_data['langs'], true);
		$params['type'] = $form_data['type'];
		$params['langkey'] = $form_data['variable'];
		foreach ($langs as $lang) {
			$params['lang'] = $lang;
			$params['val'] = $form_data[$lang];
			$saveResp = \App\Modules\Settings\LangManagement\Models\Module::addTranslation($params);
			if ($saveResp['success'] === false) {
				break;
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $saveResp['success'],
			'message' => \App\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		));
		$response->emit();
	}

	/**
	 * Save translations
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function saveTranslation(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = \App\Modules\Settings\LangManagement\Models\Module::saveTranslation($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => $saveResp['success'],
			'message' => \App\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		]);
		$response->emit();
	}

	public function saveView(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = \App\Modules\Settings\LangManagement\Models\Module::saveView($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $saveResp['success'],
			'message' => \App\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		));
		$response->emit();
	}

	/**
	 * Remove translation
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function deleteTranslation(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = \App\Modules\Settings\LangManagement\Models\Module::deleteTranslation($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => $saveResp['success'],
			'message' => \App\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		]);
		$response->emit();
	}

	public function add(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = \App\Modules\Settings\LangManagement\Models\Module::add($params);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $saveResp['success'],
			'message' => \App\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		));
		$response->emit();
	}

	public function save(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = \App\Modules\Settings\LangManagement\Models\Module::save($params);
		$response = new \App\Http\Vtiger_Response();
		if ($saveResp) {
			$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SaveDataOK', $request->getModule(false))));
		} else {
			$response->setResult(array('success' => false));
		}
		$response->emit();
	}

	public function delete(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = \App\Modules\Settings\LangManagement\Models\Module::delete($params);
		$response = new \App\Http\Vtiger_Response();
		if ($saveResp) {
			$response->setResult(['success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_DeleteDataOK', $request->getModule(false))]);
		} else {
			$response->setResult(['success' => false]);
		}
		$response->emit();
	}

	public function setAsDefault(\App\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = \App\Modules\Settings\LangManagement\Models\Module::setAsDefault($params);
		$response = new \App\Http\Vtiger_Response();
		if ($saveResp['success']) {
			$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SaveDataOK', $request->getModule(false)), 'prefixOld' => $saveResp['prefixOld']));
		} else {
			$response->setResult(array('success' => false));
		}
		$response->emit();
	}
}
