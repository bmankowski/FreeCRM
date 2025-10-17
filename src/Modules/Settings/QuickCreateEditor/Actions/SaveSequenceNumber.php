<?php

namespace App\Modules\Settings\QuickCreateEditor\Actions;
use App\Modules\Settings\QuickCreateEditorModels\Module;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class SaveSequenceNumber extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function __construct()
	{
		$this->exposeMethod('move');
	}

	public function move(\App\Http\Vtiger_Request $request)
	{
		$updatedFieldsList = $request->get('updatedFields');

		//This will update the fields sequence for the updated blocks
		\App\Modules\Settings\QuickCreateEditor\Models\Module::updateFieldSequenceNumber($updatedFieldsList);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array('success' => true));
		$response->emit();
	}
}
