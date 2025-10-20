<?php

namespace App\Modules\Calendar\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class ViewTypes  extends \App\Modules\Vtiger\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getViewTypes');
		$this->exposeMethod('getSharedUsersList');
	}

	public function getViewTypes(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$calendarViews = \App\Modules\Calendar\Models\Module::getCalendarViewTypes($currentUser->id);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('VIEWTYPES', $calendarViews);
		$viewer->view('CalendarViewTypes.tpl', $moduleName);
	}

	/**
	 * Function to get Shared Users
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function getSharedUsersList(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();


		$moduleName = $request->getModule();
		$sharedUsers = \App\Modules\Calendar\Models\Module::getSharedUsersOfCurrentUser($currentUser->id);
		$sharedUsersInfo = \App\Modules\Calendar\Models\Module::getSharedUsersInfoOfCurrentUser($currentUser->id);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SHAREDUSERS', $sharedUsers);
		$viewer->assign('SHAREDUSERS_INFO', $sharedUsersInfo);
		$viewer->assign('CURRENTUSER_MODEL', $currentUser);
		$viewer->view('CalendarSharedUsers.tpl', $moduleName);
	}
}
