<?php

namespace App\Modules\Settings\Github\Actions;



/**
 * Save issue to github
 * @package YetiForce.Github
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class SaveIssuesAjax extends \App\Modules\Settings\Base\Actions\Basic
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$title = $request->get('title');
		$body = $request->get('body');
		$clientModel = \App\Modules\Settings\Github\Models\Client::getInstance();
		$success = $clientModel->createIssue($body, $title);
		$success = $success ? true : false;
		$responce = new \App\Http\Vtiger_Response();
		$responce->setResult(array('success' => $success));
		$responce->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
