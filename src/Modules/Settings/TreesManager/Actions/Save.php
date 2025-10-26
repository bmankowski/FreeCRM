<?php

namespace App\Modules\Settings\TreesManager\Actions;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Save extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if (!$currentUser->isAdminUser()) {
			throw new \Exception\AppException('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Save tree
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$name = $request->get('name');
		$tree = $request->get('tree');
		$replace = $request->get('replace');
		$templatemodule = $request->get('templatemodule');

		$moduleModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance($qualifiedModuleName);
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Settings\TreesManager\Models\Record::getInstanceById($recordId);
		} else {
			$recordModel = new \App\Modules\Settings\TreesManager\Models\Record();
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
