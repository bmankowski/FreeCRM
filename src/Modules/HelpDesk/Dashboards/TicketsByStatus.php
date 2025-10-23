<?php

namespace App\Modules\HelpDesk\Dashboards;
use App\Modules\Settings\SupportProcessesModels\Module;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

use App\Http\Vtiger_Request;

class TicketsByStatus  extends \App\Modules\Vtiger\Views\Index
{

	private $conditions = false;

	public function getSearchParams($value, $assignedto = '')
	{

		$listSearchParams = [];
		$conditions = array(array('ticketstatus', 'e', $value));
		if (!empty($assignedto))
			array_push($conditions, array('assigned_user_id', 'e', $assignedto));
		$listSearchParams[] = $conditions;
		return '&viewname=All&search_params=' . json_encode($listSearchParams);
	}

	/**
	 * Function returns Tickets grouped by Status
	 * @param type $data
	 * @return array
	 */
	public function getTicketsByStatus($owner)
	{

		$moduleName = 'HelpDesk';
		$ticketStatus = \App\Modules\Settings\SupportProcesses\Models\Module::getTicketStatusNotModify();
		$query = new \App\Db\Query();
		$query->select(['priority', 'vtiger_ticketpriorities.color',
				'count' => new \yii\db\Expression('COUNT(*)'),
				'statusvalue' => new \yii\db\Expression("CASE WHEN vtiger_troubletickets.status IS NULL OR vtiger_troubletickets.status = '' THEN '' ELSE vtiger_troubletickets.status END")])
			->from('vtiger_troubletickets')
			->innerJoin('vtiger_crmentity', 'vtiger_troubletickets.ticketid = vtiger_crmentity.crmid')
			->innerJoin('vtiger_ticketstatus', 'vtiger_troubletickets.status = vtiger_ticketstatus.ticketstatus')
			->innerJoin('vtiger_ticketpriorities', 'vtiger_troubletickets.priority = vtiger_ticketpriorities.ticketpriorities')
			->where(['vtiger_crmentity.deleted' => 0]);

		if (!empty($owner)) {
			$query->andWhere(['smownerid' => $owner]);
		}
		if (!empty($ticketStatus)) {
			$query->andWhere(['not in', 'vtiger_troubletickets.status', $ticketStatus]);
			$this->conditions = ['condition' => ['not in', 'vtiger_troubletickets.status', $ticketStatus]];
		}
		\App\PrivilegeQuery::getConditions($query, $moduleName);
		$query->groupBy(['statusvalue', 'priority', 'vtiger_ticketpriorities.color', 'vtiger_ticketstatus.sortorderid'])->orderBy('vtiger_ticketstatus.sortorderid');
		$dataReader = $query->createCommand()->query();
		$colors = $status = $priorities = $tickets = $response = [];
		$counter = 0;

		while ($row = $dataReader->read()) {
			$tickets[$row['statusvalue']][$row['priority']] = $row['count'];
			if (!array_key_exists($row['priority'], $priorities)) {
				$priorities[$row['priority']] = $counter++;
				$colors[$row['priority']] = $row['color'];
			}
			if (!in_array($row['statusvalue'], $status))
				$status[] = $row['statusvalue'];
		}
		if (!empty($tickets)) {
			$counter = 0;
			$result = [];

			foreach ($tickets as $ticketKey => $ticketValue) {
				foreach ($priorities as $priorityKey => $priorityValue) {
					$result[$priorityValue]['data'][$counter][0] = $counter;
					$result[$priorityValue]['label'] = \App\Runtime\Vtiger_Language_Handler::translate($priorityKey, $moduleName);
					$result[$priorityValue]['color'] = $colors[$priorityKey];
					if ($ticketValue[$priorityKey]) {
						$result[$priorityValue]['data'][$counter][1] = $ticketValue[$priorityKey];
					} else {
						$result[$priorityValue]['data'][$counter][1] = 0;
					}
				}
				$counter++;
			}

			$ticks = [];
			foreach ($status as $key => $value) {
				$newArray = [$key, \App\Runtime\Vtiger_Language_Handler::translate($value, $moduleName)];
				array_push($ticks, $newArray);
				$name[] = $value;
			}

			$response['chart'] = $result;
			$response['ticks'] = $ticks;
			$response['name'] = $name;
		}
		return $response;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$linkId = $request->get('linkid');
		$data = $request->get('data');
		$createdTime = $request->get('createdtime');
		$widget = \App\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		if (!$request->has('owner'))
			$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget, $moduleName);
		else
			$owner = $request->get('owner');
		$ownerForwarded = $owner;
		if ($owner == 'all')
			$owner = '';

		//Date conversion from user to database format
		if (!empty($createdTime)) {
			$dates['start'] = \App\Modules\Vtiger\UiTypes\Date::getDBInsertedValue($createdTime['start']);
			$dates['end'] = \App\Modules\Vtiger\UiTypes\Date::getDBInsertedValue($createdTime['end']);
		}

		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$data = ($owner === false) ? [] : $this->getTicketsByStatus($owner);

		$listViewUrl = $moduleModel->getListViewUrl();
		$statusmount = count($data['name']);
		for ($i = 0; $i < $statusmount; $i++) {
			$data['links'][$i][0] = $i;
			$data['links'][$i][1] = $listViewUrl . $this->getSearchParams($data['name'][$i], $owner);
		}

		$viewer->assign('USER_CONDITIONS', $this->conditions);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('OWNER', $ownerForwarded);

		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/TicketsByStatus.tpl', $moduleName);
		}
	}
}
