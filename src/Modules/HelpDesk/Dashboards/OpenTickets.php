<?php

namespace App\Modules\HelpDesk\Dashboards;
use App\Modules\Settings\SupportProcessesModels\Module;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************************************************************** */

use App\Http\Vtiger_Request;

class OpenTickets  extends \App\Modules\Base\Views\Index
{

	/**
	 * Function returns Tickets grouped by Status
	 * @param type $data
	 * @return array
	 */
	public function getOpenTickets()
	{

		$ticketStatus = \App\Modules\Settings\SupportProcesses\Models\Module::getTicketStatusNotModify();
		$moduleName = 'HelpDesk';
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$query = new \App\Db\Query();
		$userNameSql = \App\Utils\ModuleUtils::getSqlForNameInDisplayFormat('Users');
		$query->select(['count' => new \yii\db\Expression('COUNT(*)'),
				'name' => new \yii\db\Expression("CASE WHEN ($userNameSql NOT LIKE '') THEN $userNameSql ELSE vtiger_groups.groupname END"),
				'color' => new \yii\db\Expression("CASE WHEN ($userNameSql NOT LIKE '') THEN
					vtiger_users.cal_color ELSE vtiger_groups.color END"),
				'id' => 'smownerid'])
			->from('vtiger_troubletickets')
			->innerJoin('vtiger_crmentity', 'vtiger_troubletickets.ticketid = vtiger_crmentity.crmid')
			->leftJoin('vtiger_users', 'vtiger_crmentity.smownerid = vtiger_users.id')
			->leftJoin('vtiger_groups', 'vtiger_crmentity.smownerid = vtiger_groups.groupid')
			->where(['vtiger_crmentity.deleted' => 0]);
		\App\Security\PrivilegeQuery::getConditions($query, $moduleName);
		if (!empty($ticketStatus)) {
			$query->andWhere(['not in', 'vtiger_troubletickets.status', $ticketStatus]);
		}
		$query->groupBy(['smownerid', 'vtiger_users.last_name', 'vtiger_users.last_name', 'vtiger_users.first_name', 'vtiger_groups.groupname', 'vtiger_users.cal_color'
			, 'vtiger_groups.color']);
		$dataReader = $query->createCommand()->query();
		$listViewUrl = $moduleModel->getListViewUrl();
		$chartData = [];
		while ($row = $dataReader->read()) {
			$chartData [] = [
				$row['count'],
				trim($row['name']),
				$listViewUrl . $this->getSearchParams($row['id']),
				$row['color']
			];
		}
		return $chartData;
	}

	public function getSearchParams($value)
	{
		$openTicketsStatus = \App\Modules\Settings\SupportProcesses\Models\Module::getOpenTicketStatus();
		if ($openTicketsStatus)
			$openTicketsStatus = implode(',', $openTicketsStatus);
		else {
			$allTicketStatus = \App\Modules\Settings\SupportProcesses\Models\Module::getAllTicketStatus();
			$openTicketsStatus = implode(',', $allTicketStatus);
		}

		$listSearchParams = [];

		$conditions = array(array('assigned_user_id', 'e', $value));
		array_push($conditions, array('ticketstatus', 'e', "$openTicketsStatus"));
		$listSearchParams[] = $conditions;
		return '&viewname=All&search_params=' . json_encode($listSearchParams);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$linkId = $request->get('linkid');
		$widget = \App\Modules\Base\Models\Widget::getInstance($linkId, $currentUser->getId());
		$data = $this->getOpenTickets();
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		//Include special script and css needed for this widget
		$viewer->assign('CURRENTUSER', $currentUser);

		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/OpenTickets.tpl', $moduleName);
		}
	}
}
