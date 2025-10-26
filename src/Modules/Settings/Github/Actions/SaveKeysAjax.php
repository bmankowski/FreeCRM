<?php

namespace App\Modules\Settings\Github\Actions;



/**
 * Save keys
 * @package YetiForce.Github
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class SaveKeysAjax extends \App\Modules\Settings\Base\Actions\Basic
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$clientId = $request->get('client_id');
		$token = $request->get('token');
		$username = $request->get('username');
		$clientModel = \App\Modules\Settings\Github\Models\Client::getInstance();
		$clientModel->setToken($token);
		$clientModel->setClientId($clientId);
		$clientModel->setUsername($username);
		if ($clientModel->checkToken()) {
			$success = $clientModel->saveKeys();
			$success = $success ? true : false;
		} else {
			$success = false;
		}
		$responce = new \App\Http\Vtiger_Response();
		$responce->setResult(array('success' => $success));
		$responce->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
