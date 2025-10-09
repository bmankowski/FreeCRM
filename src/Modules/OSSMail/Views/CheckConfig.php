<?php

namespace FreeCRM\Modules\OSSMail\Views;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class CheckConfig extends View
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		require_once ROOT_DIRECTORY . '/modules/OSSMail/views/CheckConfigCore.php';
	}
}
