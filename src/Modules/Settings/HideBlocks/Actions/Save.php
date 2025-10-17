<?php

namespace App\Modules\Settings\HideBlocks\Actions;
use App\Modules\Settings\HideBlocksModels\Module;
use App\Modules\Settings\HideBlocksModels\Record as Settings_HideBlocks_Record_Model;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Save extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$blockId = $request->get('blockid');
		$enabled = $request->get('enabled');
		$conditions = $request->get('conditions');
		$views = $request->get('views');
		$qualifiedModuleName = $request->getModule(false);
		if ($recordId) {
			$recordModel = Settings_HideBlocks_Record_Model::getInstanceById($recordId, $qualifiedModuleName);
		} else {
			$recordModel = Settings_HideBlocks_Record_Model::getCleanInstance($qualifiedModuleName);
		}
		$recordModel->set('blockid', $blockId);
		$recordModel->set('enabled', $enabled);
		$recordModel->set('conditions', $conditions);
		$recordModel->set('views', $views);
		$recordModel->save();
		$returnUrl = $recordModel->getDetailViewUrl();
		header("Location: " . \App\Modules\Settings\HideBlocks\Models\Module::getListViewUrl());
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
