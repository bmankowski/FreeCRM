<?php

namespace FreeCRM\Modules\Settings\Github\Actions;
use FreeCRM\Modules\Settings\GithubModels\Client as Settings_Github_Client_Model;



/**
 * Save keys
 * @package YetiForce.Github
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class SaveKeysAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Basic
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$clientId = $request->get('client_id');
		$token = $request->get('token');
		$username = $request->get('username');
		$clientModel = Settings_Github_Client_Model::getInstance();
		$clientModel->setToken($token);
		$clientModel->setClientId($clientId);
		$clientModel->setUsername($username);
		if ($clientModel->checkToken()) {
			$success = $clientModel->saveKeys();
			$success = $success ? true : false;
		} else {
			$success = false;
		}
		$responce = new \FreeCRM\Http\Vtiger_Response();
		$responce->setResult(array('success' => $success));
		$responce->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
