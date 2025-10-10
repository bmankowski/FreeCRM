<?php

namespace FreeCRM\Modules\Calendar\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

require_once ROOT_DIRECTORY . '/src/Webservices/Query.php';

class Feed extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		try {
			$result = array();

			$start = $request->get('start');
			$end = $request->get('end');
			$type = $request->get('type');
			$userid = $request->get('userid');
			$color = $request->get('color');
			$textColor = $request->get('textColor');

		$actionName = 'Calendar_' . $type . '_ActivityTypes';
		$pullInstance = new $actionName;
			$pullInstance->process($this, $request, $start, $end, $result, $userid, $color, $textColor);
			echo json_encode($result);
		} catch (Exception $ex) {
			echo $ex->getMessage();
		}
	}

	public function getGroupsIdsForUsers($userId)
	{
		require_once ROOT_DIRECTORY . '/src/utils/GetUserGroups.php';

		$userGroupInstance = new GetUserGroups();
		$userGroupInstance->getAllUserGroups($userId);
		return $userGroupInstance->user_groups;
	}

	public function queryForRecords($query, $onlymine = true)
	{
		$user = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if ($onlymine) {
			$groupIds = $this->getGroupsIdsForUsers($user->getId());
			$groupWsIds = array();
			foreach ($groupIds as $groupId) {
				$groupWsIds[] = vtws_getWebserviceEntityId('Groups', $groupId);
			}
			$userwsid = vtws_getWebserviceEntityId('Users', $user->getId());
			$userAndGroupIds = array_merge(array($userwsid), $groupWsIds);
			$query .= " && assigned_user_id IN ('" . implode("','", $userAndGroupIds) . "')";
		}
		return vtws_query($query . ';', $user);
	}
}
