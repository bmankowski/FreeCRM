<?php

namespace App\Modules\Calendar\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class Reminders  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		if ('true' == $request->get('type_remainder')) {
			$recordModels = \App\Modules\Calendar\Models\Module::getCalendarReminder(true);
		} else {
			$recordModels = \App\Modules\Calendar\Models\Module::getCalendarReminder();
		}
		$colorList = [];
		foreach ($recordModels as $record) {
			$record->updateReminderStatus(2);
			$linkId = $record->get('link');
			if ($linkId) {
				$record->set('link_module_name', \App\Record::getType($linkId));
			}
			$colorList[$record->getId()] = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers($moduleName, $record->getId(), $record);
		}
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);
		$permissionToSendEmail = $permission && \App\AppConfig::main('isActiveSendingMails') && \App\Modules\Users\Models\Privileges::isPermitted('OSSMail');
		$viewer->assign('COLOR_LIST', array_filter($colorList));
		$viewer->assign('PERMISSION_TO_SENDE_MAIL', $permissionToSendEmail);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('RECORDS', $recordModels);
		$viewer->view('Reminders.tpl', $moduleName);
	}
}
