<?php

namespace App\Modules\Settings\DataAccess\Actions;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class ExecuteHandlers extends \App\Modules\Settings\Base\Actions\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('param');
		$Resp = \App\Modules\Settings\DataAccess\Models\Module::executeAjaxHandlers($param['module'], $param);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => $Resp['success'],
			'data' => $Resp['data'])
		);
		$response->emit();
	}
}
