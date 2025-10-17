<?php

namespace FreeCRM\Modules\Settings\Mail\Actions;
use FreeCRM\Modules\Settings\MailModels\Autologin;


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

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('updateUsers');
		$this->exposeMethod('updateConfig');
		$this->exposeMethod('updateSignature');
		$this->exposeMethod('acceptanceRecord');
	}

	public function updateUsers(\FreeCRM\Http\Vtiger_Request $request)
	{
		$id = $request->get('id');
		$user = $request->get('user');
		\FreeCRM\Modules\Settings\Mail\Models\Autologin::updateUsersAutologin($id, $user);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_CHANGES', $request->getModule(false))
		]);
		$response->emit();
	}

	public function updateConfig(\FreeCRM\Http\Vtiger_Request $request)
	{
		$name = $request->get('name');
		$val = $request->get('val');
		$type = $request->get('type');
		\FreeCRM\Modules\Settings\Mail\Models\Config::updateConfig($name, $val, $type);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_CHANGES', $request->getModule(false))
		]);
		$response->emit();
	}

	public function updateSignature(\FreeCRM\Http\Vtiger_Request $request)
	{
		$val = $request->get('val');
		\FreeCRM\Modules\Settings\Mail\Models\Config::updateConfig('signature', $val, 'signature');
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_SIGNATURE', $request->getModule(false))
		]);
		$response->emit();
	}
	
	public function acceptanceRecord(\FreeCRM\Http\Vtiger_Request $request)
	{
		\FreeCRM\Modules\Settings\Mail\Models\Config::acceptanceRecord($request->get('id'));
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_ACCEPTED', $request->getModule(false))
		]);
		$response->emit();
	}
}
