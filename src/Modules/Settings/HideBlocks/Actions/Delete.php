<?php

namespace App\Modules\Settings\HideBlocks\Actions;
use App\Modules\Settings\HideBlocksModels\Module;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Delete extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = Settings_HideBlocks_Record_Model::getInstanceById($recordId, $qualifiedModuleName);
		$recordModel->delete();

		$returnUrl = \App\Modules\Settings\HideBlocks\Models\Module::getListViewUrl();
		header("Location: $returnUrl");
	}
}
