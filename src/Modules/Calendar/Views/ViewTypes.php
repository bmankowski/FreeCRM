<?php

namespace FreeCRM\Modules\Calendar\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class ViewTypes extends \Vtiger_Index_View
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getViewTypes');
		$this->exposeMethod('getSharedUsersList');
	}

	public function getViewTypes(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$calendarViews = \FreeCRM\Modules\Calendar\Models\Module::getCalendarViewTypes($currentUser->id);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('VIEWTYPES', $calendarViews);
		$viewer->view('CalendarViewTypes.tpl', $moduleName);
	}

	/**
	 * Function to get Shared Users
	 * @param Vtiger_Request $request
	 */
	public function getSharedUsersList(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();


		$moduleName = $request->getModule();
		$sharedUsers = \FreeCRM\Modules\Calendar\Models\Module::getSharedUsersOfCurrentUser($currentUser->id);
		$sharedUsersInfo = \FreeCRM\Modules\Calendar\Models\Module::getSharedUsersInfoOfCurrentUser($currentUser->id);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SHAREDUSERS', $sharedUsers);
		$viewer->assign('SHAREDUSERS_INFO', $sharedUsersInfo);
		$viewer->assign('CURRENTUSER_MODEL', $currentUser);
		$viewer->view('CalendarSharedUsers.tpl', $moduleName);
	}
}
