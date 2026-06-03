<?php

namespace App\Modules\Settings\Mail\Actions;
use App\Modules\Settings\MailModels\Autologin;


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
		$this->exposeMethod('updateUsers');
		$this->exposeMethod('updateConfig');
		$this->exposeMethod('acceptanceRecord');
	}

	public function updateUsers(\App\Http\Vtiger_Request $request)
	{
		$id = $request->get('id');
		$user = $request->get('user');
		\App\Modules\Settings\Mail\Models\Autologin::updateUsersAutologin($id, $user);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_CHANGES', $request->getModule(false))
		]);
		$response->emit();
	}

	public function updateConfig(\App\Http\Vtiger_Request $request)
	{
		$name = $request->get('name');
		$val = $request->get('val');
		$type = $request->get('type');
		\App\Modules\Settings\Mail\Models\Config::updateConfig($name, $val, $type);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_CHANGES', $request->getModule(false))
		]);
		$response->emit();
	}

	public function acceptanceRecord(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Settings\Mail\Models\Config::acceptanceRecord($request->get('id'));
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_ACCEPTED', $request->getModule(false))
		]);
		$response->emit();
	}
}
