<?php

namespace App\Modules\Settings\Groups\Actions;
use App\Modules\Settings\Vtiger\Models\Tracker;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Save extends \App\Modules\Settings\Vtiger\Actions\Save
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');

		$moduleModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance($qualifiedModuleName);
		$prevValues = [];
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Settings\Groups\Models\Record::getInstance($recordId);
			$members = $recordModel->getMembers();
			$membersToDipslay = [];
			foreach ($members as $typeMembers) {
				foreach ($typeMembers as $member) {
					$membersToDipslay[] = $member->get('id');
				}
			}
			$recordModel->set('group_members', $membersToDipslay);
			$recordModel->set('modules', $recordModel->getModules());
			$prevValues = $recordModel->getDisplayData();
		} else {
			$recordModel = new \App\Modules\Settings\Groups\Models\Record();
		}
		if ($recordModel) {
			$recordModel->set('groupname', \App\Utils\ListViewUtils::decodeHtml($request->get('groupname')));
			$recordModel->set('description', $request->get('description'));
			$recordModel->set('group_members', $request->get('members'));
			$recordModel->set('modules', $request->get('modules'));
			$recordModel->save();
			$postValues = $recordModel->getDisplayData();
			\App\Modules\Settings\Vtiger\Models\Tracker::addDetail($prevValues, $postValues);
		}

		$redirectUrl = $recordModel->getDetailViewUrl();
		header("Location: $redirectUrl");
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
