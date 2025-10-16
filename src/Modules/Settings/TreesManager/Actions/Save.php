<?php

namespace FreeCRM\Modules\Settings\TreesManager\Actions;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

use FreeCRM\Modules\Settings\TreesManager\Models\Record as Settings_TreesManager_Record_Model;
class Save extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUser->isAdminUser()) {
			throw new \Exception\AppException('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Save tree
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$name = $request->get('name');
		$tree = $request->get('tree');
		$replace = $request->get('replace');
		$templatemodule = $request->get('templatemodule');

		$moduleModel = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
		if (!empty($recordId)) {
			$recordModel = Settings_TreesManager_Record_Model::getInstanceById($recordId);
		} else {
			$recordModel = new Settings_TreesManager_Record_Model();
		}
		$recordModel->set('name', $name);
		$recordModel->set('module', $templatemodule);
		$recordModel->set('tree', $tree);
		$recordModel->set('share', $request->get('share'));
		$recordModel->set('replace', $replace);
		$recordModel->save();
		header('Location: ' . $moduleModel->getListViewUrl());
	}
}
