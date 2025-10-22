<?php

namespace App\Modules\Vtiger\Models;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class DashBoard extends \App\Runtime\BaseModel
{

	/**
	 * Function to get Module instance
	 * @return \App\Modules\Vtiger\Models\Module
	 */
	public function getModule()
	{
		return $this->module;
	}

	/**
	 * Function to set the module instance
	 * @param \App\Modules\Vtiger\Models\Module $moduleInstance - module model
	 * @return \App\Modules\Vtiger\Models\DetailView
	 */
	public function setModule($moduleInstance)
	{
		$this->module = $moduleInstance;
		return $this;
	}

	/**
	 *  Function to get the module name
	 *  @return string - name of the module
	 */
	public function getModuleName()
	{
		return $this->getModule()->get('name');
	}

	/**
	 * Function returns List of User's selected Dashboard Widgets
	 * @return <Array of \App\Modules\Vtiger\Models\Widget>
	 */
	public function getDashboards($action = 1)
	{

		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$currentUserPrivilegeModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$moduleModel = $this->getModule();

		if ($action == 'Header')
			$action = 0;
		$query = (new \App\Db\Query())->select('vtiger_links.*, mdw.userid, mdw.data, mdw.active, mdw.title, mdw.size, mdw.filterid,
					mdw.id AS widgetid, mdw.position, vtiger_links.linkid AS id, mdw.limit, mdw.cache, mdw.owners, mdw.isdefault')
			->from('vtiger_links')
			->leftJoin('vtiger_module_dashboard_widgets mdw', 'vtiger_links.linkid = mdw.linkid')
			->where(['mdw.userid' => $currentUser->getId(), 'vtiger_links.linktype' => 'DASHBOARDWIDGET', 'mdw.module' => $moduleModel->getId(), 'active' => $action, 'mdw.dashboardid' => $this->get('dashboardId')]);
		$dataReader = $query->createCommand()->query();
		$widgets = [];

		while ($row = $dataReader->read()) {
			$row['linkid'] = $row['id'];
			if ($row['linklabel'] == 'Mini List') {
				if (!$row['isdefault'])
					$row['deleteFromList'] = true;
				$minilistWidget = \App\Modules\Vtiger\Models\Widget::getInstanceFromValues($row);
				$minilistWidgetModel = new \App\Modules\Vtiger\Models\MiniList();
				$minilistWidgetModel->setWidgetModel($minilistWidget);
				$minilistWidget->set('title', $minilistWidgetModel->getTitle());
				$widgets[] = $minilistWidget;
			} elseif ($row['linklabel'] == 'ChartFilter') {
				if (!$row['isdefault'])
					$row['deleteFromList'] = true;
				$charFilterWidget = \App\Modules\Vtiger\Models\Widget::getInstanceFromValues($row);
				$chartFilterWidgetModel = new \App\Modules\Vtiger\Models\ChartFilter();
				$chartFilterWidgetModel->setWidgetModel($charFilterWidget);
				$charFilterWidget->set('title', $chartFilterWidgetModel->getTitle());
				$widgets[] = $charFilterWidget;
			} else
				$widgets[] = \App\Modules\Vtiger\Models\Widget::getInstanceFromValues($row);
		}

		foreach ($widgets as $index => $widget) {
			$label = $widget->get('linklabel');
			$url = $widget->get('linkurl');
			$data = $widget->get('data');
			$filterid = $widget->get('filterid');
			$module = $this->getModuleNameFromLink($url, $label);

			if ($module == 'Home' && !empty($filterid) && !empty($data)) {
				$filterData = \App\Json::decode(htmlspecialchars_decode($data));
				$module = $filterData['module'];
			}
			if (!$currentUserPrivilegeModel->hasModulePermission($module)) {
				unset($widgets[$index]);
			}
		}

		return $widgets;
	}

	/**
	 * Function to get the module name of a widget using linkurl
	 * @param string $linkUrl
	 * @param string $linkLabel
	 * @return string $module - Module Name
	 */
	public function getModuleNameFromLink($linkUrl, $linkLabel)
	{
		$params = \vtlib\Functions::getQueryParams($linkUrl);
		$module = $params['module'];
		if ($linkLabel == 'Overdue Activities' || $linkLabel == 'Upcoming Activities') {
			$module = 'Calendar';
		}
		return $module;
	}

	/**
	 * Function to get the default widgets(Deprecated)
	 * @return \App\Modules\Vtiger\Models\Widget[]
	 */
	public function getDefaultWidgets()
	{
		$moduleModel = $this->getModule();
		$widgets = [];

		return $widgets;
	}

	public function verifyDashboard($moduleName)
	{
		\App\Log::trace('Entering ' . __METHOD__ . '(' . $moduleName . ')');
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$blockId = \App\Modules\Settings\WidgetsManagement\Models\Module::getBlocksFromModule($moduleName, $currentUser->getRole(), $this->get('dashboardId'));
		if (count($blockId) == 0) {
			\App\Log::trace('Exiting ' . __METHOD__);
			return;
		}
		$dataReader = (new \App\Db\Query())->select('vtiger_module_dashboard.*, vtiger_links.tabid')
				->from('vtiger_module_dashboard')
				->innerJoin('vtiger_links', 'vtiger_links.linkid = vtiger_module_dashboard.linkid')
				->where(['vtiger_module_dashboard.blockid' => $blockId])
				->createCommand()->query();
		while ($row = $dataReader->read()) {
			$row['data'] = htmlspecialchars_decode($row['data']);
			$row['size'] = htmlspecialchars_decode($row['size']);
			$row['owners'] = htmlspecialchars_decode($row['owners']);
			if (!(new \App\Db\Query())->from('vtiger_module_dashboard_widgets')
					->where(['userid' => $currentUser->getId(), 'templateid' => $row['id']])
					->exists()) {
				$active = $row['isdefault'] ? 1 : 0;
				\App\Db::getInstance()->createCommand()->insert('vtiger_module_dashboard_widgets', [
					'linkid' => $row['linkid'],
					'userid' => $currentUser->getId(),
					'templateid' => $row['id'],
					'filterid' => $row['filterid'],
					'title' => $row['title'],
					'data' => $row['data'],
					'size' => $row['size'],
					'limit' => $row['limit'],
					'owners' => $row['owners'],
					'isdefault' => $row['isdefault'],
					'active' => $active,
					'module' => $row['tabid'],
					'cache' => $row['cache'],
					'date' => $row['date'],
					'dashboardid' => $this->get('dashboardId')
				])->execute();
			}
		}
		\App\Log::trace('Exiting ' . __METHOD__);
	}

	/**
	 * Function to get the instance
	 * @param string $moduleName - module name
	 * @return \App\Modules\Vtiger\Models\DashBoard
	 */
	public static function getInstance($moduleName)
	{
		$modelClassName = \App\Loader::getComponentClassName('Model', 'DashBoard', $moduleName);
		$instance = new $modelClassName();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		return $instance->setModule($moduleModel);
	}

	/**
	 * Function to get modules with widgets
	 * @param string $moduleName - module name
	 * @return <Array> $modules
	 */
	public static function getModulesWithWidgets($moduleName = false, $dashboard)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();

		$query = (new \App\Db\Query())->select('vtiger_module_dashboard_widgets.module, vtiger_module_dashboard_blocks.tabid')
			->from('vtiger_module_dashboard')
			->leftJoin('vtiger_module_dashboard_blocks', 'vtiger_module_dashboard_blocks.id = vtiger_module_dashboard.blockid')
			->leftJoin('vtiger_module_dashboard_widgets', 'vtiger_module_dashboard_widgets.templateid = vtiger_module_dashboard.id')
			->where(['userid' => $currentUser->getId(), 'vtiger_module_dashboard_widgets.dashboardid' => $dashboard])
			->orWhere(['authorized' => $currentUser->getRole()])
			->groupBy('module, tabid');
		$dataReader = $query->createCommand()->query();
		$modules = [];
		while ($row = $dataReader->read()) {
			$tabId = $row['module'] ? $row['module'] : $row['tabid'];
			if (!isset($modules[$tabId])) {
				$modules[$tabId] = \vtlib\Functions::getModuleName($tabId);
			}
		}
		ksort($modules);
		if ($moduleName && ($tabId = \vtlib\Functions::getModuleId($moduleName))) {
			unset($modules[$tabId]);
			$modules = array_merge([$tabId => $moduleName], $modules);
		}
		return $modules;
	}
}
