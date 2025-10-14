<?php

namespace FreeCRM\Modules\Settings\SMSNotifier\Actions;
use FreeCRM\Modules\Settings\SMSNotifierModels\Record;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		if ($recordId) {
			$recordModel = \FreeCRM\Modules\Settings\SMSNotifier\Models\Record::getInstanceById($recordId, $qualifiedModuleName);
		} else {
			$recordModel = \FreeCRM\Modules\Settings\SMSNotifier\Models\Record::getCleanInstance($qualifiedModuleName);
		}

		$editableFields = $recordModel->getEditableFields();
		foreach ($editableFields as $fieldName => $fieldModel) {
			$recordModel->set($fieldName, $request->get($fieldName));
		}

		$parameters = '';
		$selectedProvider = $request->get('providertype');
		$allProviders = $recordModel->getModule()->getAllProviders();
		foreach ($allProviders as $provider) {
			if ($provider->getName() === $selectedProvider) {
				$fieldsInfo = \FreeCRM\Modules\Settings\SMSNotifier\Models\ProviderField::getInstanceByProvider($provider);
				foreach ($fieldsInfo as $fieldInfo) {
					$recordModel->set($fieldInfo['name'], $request->get($fieldInfo['name']));
					$parameters[$fieldInfo['name']] = $request->get($fieldInfo['name']);
				}
				$recordModel->set('parameters', \App\Json::encode($parameters));
				break;
			}
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			$recordModel->save();
			$response->setResult(array(vtranslate('LBL_SAVED_SUCCESSFULLY', $qualifiedModuleName)));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
