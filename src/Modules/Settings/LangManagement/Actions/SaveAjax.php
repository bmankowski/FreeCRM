<?php

namespace FreeCRM\Modules\Settings\LangManagement\Actions;
use FreeCRM\Modules\Settings\LangManagement\Models\Module as Settings_LangManagement_Module_Model;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
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

	public function addTranslation(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$form_data = $params['form_data'];
		$langs = json_decode($form_data['langs'], true);
		$params['type'] = $form_data['type'];
		$params['langkey'] = $form_data['variable'];
		foreach ($langs as $lang) {
			$params['lang'] = $lang;
			$params['val'] = $form_data[$lang];
			$saveResp = Settings_LangManagement_Module_Model::addTranslation($params);
			if ($saveResp['success'] === false) {
				break;
			}
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $saveResp['success'],
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		));
		$response->emit();
	}

	/**
	 * Save translations
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function saveTranslation(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = Settings_LangManagement_Module_Model::saveTranslation($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'success' => $saveResp['success'],
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		]);
		$response->emit();
	}

	public function saveView(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = Settings_LangManagement_Module_Model::saveView($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $saveResp['success'],
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		));
		$response->emit();
	}

	/**
	 * Remove translation
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function deleteTranslation(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = Settings_LangManagement_Module_Model::deleteTranslation($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'success' => $saveResp['success'],
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		]);
		$response->emit();
	}

	public function add(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = Settings_LangManagement_Module_Model::add($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $saveResp['success'],
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate($saveResp['data'], $request->getModule(false))
		));
		$response->emit();
	}

	public function save(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = Settings_LangManagement_Module_Model::save($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		if ($saveResp) {
			$response->setResult(array('success' => true, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SaveDataOK', $request->getModule(false))));
		} else {
			$response->setResult(array('success' => false));
		}
		$response->emit();
	}

	public function delete(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = Settings_LangManagement_Module_Model::delete($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		if ($saveResp) {
			$response->setResult(['success' => true, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_DeleteDataOK', $request->getModule(false))]);
		} else {
			$response->setResult(['success' => false]);
		}
		$response->emit();
	}

	public function setAsDefault(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		$saveResp = Settings_LangManagement_Module_Model::setAsDefault($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		if ($saveResp['success']) {
			$response->setResult(array('success' => true, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SaveDataOK', $request->getModule(false)), 'prefixOld' => $saveResp['prefixOld']));
		} else {
			$response->setResult(array('success' => false));
		}
		$response->emit();
	}
}
