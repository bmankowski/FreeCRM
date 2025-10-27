<?php

namespace App\Modules\Settings\Base\Models;
use App\Modules\Settings\Base\Models\Menu;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/*
 * Vtiger Settings MenuItem Model Class
 */

class MenuItem extends \App\Modules\Base\Models\Record
{
	/**
	 * @var \App\Modules\Settings\Base\Models\Menu
	 */
	public $menu;

	protected static $itemsTable = 'vtiger_settings_field';
	protected static $itemId = 'fieldid';
	public static $transformedUrlMapping = array(
		'index.php?module=Administration&action=index&parenttab=Settings' => 'index.php?module=Users&parent=Settings&view=List',
		'index.php?module=Settings&action=listroles&parenttab=Settings' => 'index.php?module=Roles&parent=Settings&view=Index',
		'index.php?module=Settings&action=ListProfiles&parenttab=Settings' => 'index.php?module=Profiles&parent=Settings&view=List',
		'index.php?module=Settings&action=listgroups&parenttab=Settings' => 'index.php?module=Groups&parent=Settings&view=List',
		'index.php?module=Settings&action=OrgSharingDetailView&parenttab=Settings' => 'index.php?module=SharingAccess&parent=Settings&view=Index',
		'index.php?module=Settings&action=DefaultFieldPermissions&parenttab=Settings' => 'index.php?module=FieldAccess&parent=Settings&view=Index',
		'index.php?module=Settings&action=ListLoginHistory&parenttab=Settings' => 'index.php?module=LoginHistory&parent=Settings&view=List',
		'index.php?module=Settings&action=ModuleManager&parenttab=Settings' => 'index.php?module=ModuleManager&parent=Settings&view=List',
		'index.php?module=PickList&action=PickList&parenttab=Settings' => 'index.php?parent=Settings&module=Picklist&view=Index',
		'index.php?module=Settings&action=listwordtemplates&parenttab=Settings' => 'index.php?module=Settings&submodule=ModuleManager&view=WordTemplates',
		'index.php?module=Settings&action=listnotificationschedulers&parenttab=Settings' => 'index.php?module=Settings&submodule=Vtiger&view=Schedulers',
		'index.php?module=Settings&action=listinventorynotifications&parenttab=Settings' => 'index.php?module=Settings&submodule=Notifications&view=InventoryAlerts',
		'index.php?module=Settings&action=CurrencyListView&parenttab=Settings' => 'index.php?parent=Settings&module=Currency&view=List',
		'index.php?module=Settings&action=TaxConfig&parenttab=Settings' => 'index.php?module=Vtiger&parent=Settings&view=TaxIndex',
		'index.php?module=Settings&action=ProxyServerConfig&parenttab=Settings' => 'index.php?module=Settings&submodule=Server&view=ProxyConfig',
		'index.php?module=Settings&action=OrganizationTermsandConditions&parenttab=Settings' => 'index.php?parent=Settings&module=Vtiger&view=TermsAndConditionsEdit',
		'index.php?module=Settings&action=CustomModEntityNo&parenttab=Settings' => 'index.php?module=Vtiger&parent=Settings&view=CustomRecordNumbering',
		'index.php?module=com_vtiger_workflow&action=workflowlist&parenttab=Settings' => 'index.php?module=Workflows&parent=Settings&view=List',
		'index.php?module=com_vtiger_workflow&action=workflowlist' => 'index.php?module=Workflows&parent=Settings&view=List',
		'index.php?module=ConfigEditor&action=index' => 'index.php?module=Vtiger&parent=Settings&view=ConfigEditorDetail',
		'index.php?module=Tooltip&action=QuickView&parenttab=Settings' => 'index.php?module=Settings&submodule=Tooltip&view=Index',
		'index.php?module=Settings&action=Announcements&parenttab=Settings' => 'index.php?parent=Settings&module=Vtiger&view=AnnouncementEdit',
		'index.php?module=PickList&action=PickListDependencySetup&parenttab=Settings' => 'index.php?parent=Settings&module=PickListDependency&view=List',
		'index.php?module=ModTracker&action=BasicSettings&parenttab=Settings&formodule=ModTracker' => 'index.php?module=Settings&submodule=ModTracker&view=Index',
		'index.php?module=CronTasks&action=ListCronJobs&parenttab=Settings' => 'index.php?module=CronTasks&parent=Settings&view=List',
		'index.php?module=ExchangeConnector&action=index&parenttab=Settings' => 'index.php?module=ExchangeConnector&parent=Settings&view=Index'
	);

	/**
	 * Function to get the Id of the menu item
	 * @return <Number> - Menu Item Id
	 */
	public function getId()
	{
		return $this->get(self::$itemId);
	}

	/**
	 * Function to get the Menu to which the Item belongs
	 * @return \App\Modules\Settings\Base\Models\Menu instance
	 */
	public function getMenu()
	{
		return $this->menu;
	}

	/**
	 * Function to set the Menu to which the Item belongs, given Menu Id
	 * @param <Number> $menuId
	 * @return \App\Modules\Settings\Base\Models\MenuItem
	 */
	public function setMenu($menuId)
	{
		$this->menu = \App\Modules\Settings\Base\Models\Menu::getInstanceById($menuId);
		return $this;
	}

	/**
	 * Function to set the Menu to which the Item belongs, given Menu Model instance
	 * @param <\App\Modules\Settings\Base\Models\Menu> $menu - Settings Menu Model instance
	 * @return \App\Modules\Settings\Base\Models\MenuItem
	 */
	public function setMenuFromInstance($menu)
	{
		$this->menu = $menu;
		return $this;
	}

	/**
	 * Function to get the url to get to the Settings Menu Item
	 * @return string - Menu Item landing url
	 */
	public function getUrl()
	{
		$url = $this->get('linkto');
		$url = \App\Utils\ListViewUtils::decodeHtml($url);
		if (isset(self::$transformedUrlMapping[$url])) {
			$url = self::$transformedUrlMapping[$url];
		}
		if (!empty($this->menu)) {
			$url .= '&block=' . $this->getMenu()->getId() . '&fieldid=' . $this->getId();
		}
		return $url;
	}

	/**
	 * Function to get the module name, to which the Settings Menu Item belongs to
	 * @return string - Module to which the Menu Item belongs
	 */
	public function getModuleName()
	{
		return 'Settings:Vtiger';
	}

	/**
	 *  Function to get the pin and unpin action url
	 */
	public function getPinUnpinActionUrl()
	{
		return 'index.php?module=Vtiger&parent=Settings&action=Basic&mode=updateFieldPinnedStatus&fieldid=' . $this->getId();
	}

	/**
	 * Function to verify whether menuitem is pinned or not
	 * @return boolean true to pinned, false to not pinned.
	 */
	public function isPinned()
	{
		$pinStatus = $this->get('pinned');
		return $pinStatus == '1' ? true : false;
	}

	/**
	 * Function which will update the pin status 
	 * @param boolean $pinned - true to enable , false to disable
	 */
	private function updatePinStatus($pinned = false)
	{

		$pinnedStaus = 0;
		if ($pinned) {
			$pinnedStaus = 1;
		}
		\App\Db::getInstance()->createCommand()->update(self::$itemsTable, ['pinned' => $pinnedStaus], [self::$itemId => $this->getId()])->execute();
	}

	/**
	 * Function which will enable the field as pinned
	 */
	public function markPinned()
	{
		$this->updatePinStatus(1);
	}

	/**
	 * Function which will disable the field pinned status
	 */
	public function unMarkPinned()
	{
		$this->updatePinStatus();
	}

	/**
	 * Function to get the instance of the Menu Item model given the valuemap array
	 * @param array $valueMap
	 * @return \App\Modules\Settings\Base\Models\MenuItem instance
	 */
	public static function getInstanceFromArray($valueMap)
	{
		return new self($valueMap);
	}

	/**
	 * Function to get the instance of the Menu Item model, given name and Menu instance
	 * @param string $name
	 * @param <\App\Modules\Settings\Base\Models\Menu> $menuModel
	 * @return \App\Modules\Settings\Base\Models\MenuItem instance
	 */
	public static function getInstance($name, $menuModel = false)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$sql = sprintf('SELECT * FROM %s WHERE name = ?', self::$itemsTable);
		$params = [$name];

		if ($menuModel) {
			$sql .= ' WHERE blockid = ?';
			$params[] = $menuModel->getId();
		}
		$result = $db->pquery($sql, $params);

		if ($db->num_rows($result) > 0) {
			$rowData = $db->query_result_rowdata($result, 0);
			$menuItem = \App\Modules\Settings\Base\Models\MenuItem::getInstanceFromArray($rowData);
			if ($menuModel) {
				$menuItem->setMenuFromInstance($menuModel);
			} else {
				$menuItem->setMenu($rowData['blockid']);
			}
			return $menuItem;
		}
		return false;
	}

	/**
	 * Function to get the instance of the Menu Item model, given item id and Menu instance
	 * @param string $name
	 * @param <\App\Modules\Settings\Base\Models\Menu> $menuModel
	 * @return \App\Modules\Settings\Base\Models\MenuItem instance
	 */
	public static function getInstanceById($id, $menuModel = false)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$sql = sprintf('SELECT * FROM %s WHERE %s = ?', self::$itemsTable, self::$itemId);
		$params = array($id);

		if ($menuModel) {
			$sql .= ' WHERE blockid = ?';
			$params[] = $menuModel->getId();
		}
		$result = $db->pquery($sql, $params);

		if ($db->num_rows($result) > 0) {
			$rowData = $db->query_result_rowdata($result, 0);
			$menuItem = \App\Modules\Settings\Base\Models\MenuItem::getInstanceFromArray($rowData);
			if ($menuModel) {
				$menuItem->setMenuFromInstance($menuModel);
			} else {
				$menuItem->setMenu($rowData['blockid']);
			}
			return $menuItem;
		}
		return false;
	}

	/**
	 * Static function to get the list of all the items of the given Menu, all items if Menu is not specified
	 * @param <\App\Modules\Settings\Base\Models\Menu> $menuModel
	 * @return array - List of <\App\Modules\Settings\Base\Models\MenuItem> instances
	 */
	public static function getAll($menuModel = false, $onlyActive = true)
	{
		$skipMenuItemList = ['LBL_AUDIT_TRAIL', 'LBL_SYSTEM_INFO', 'LBL_PROXY_SETTINGS', 'LBL_DEFAULT_MODULE_VIEW',
			'LBL_FIELDFORMULAS', 'LBL_FIELDS_ACCESS', 'LBL_MAIL_MERGE', 'NOTIFICATIONSCHEDULERS',
			'INVENTORYNOTIFICATION', 'ModTracker', 'LBL_WORKFLOW_LIST', 'LBL_TOOLTIP_MANAGEMENT'];
		$query = (new \App\Db\Query())->from(self::$itemsTable);
		$conditionsSqls = [];
		if ($menuModel !== false) {
			$conditionsSqls['blockid'] = $menuModel->getId();
		}
		if ($onlyActive) {
			$conditionsSqls['active'] = 0;
		}
		if (count($conditionsSqls) > 0) {
			$query->where($conditionsSqls);
		}
		$dataReader = $query->andWhere(['and', ['NOT IN', 'name', $skipMenuItemList], ['or', ['like', 'admin_access', ',' . \App\Modules\Users\Models\Record::getCurrentUserId() . ','], ['admin_access' => null]]])
				->orderBy('sequence')
				->createCommand()->query();
		$menuItemModels = [];
		while ($rowData = $dataReader->read()) {
			$fieldId = $rowData[self::$itemId];
			$menuItem = \App\Modules\Settings\Base\Models\MenuItem::getInstanceFromArray($rowData);
			if ($menuModel) {
				$menuItem->setMenuFromInstance($menuModel);
			} else {
				$menuItem->setMenu($rowData['blockid']);
			}
			$menuItemModels[$fieldId] = $menuItem;
		}
		return $menuItemModels;
	}

	/**
	 * Function to get the pinned items 
	 * @param array of fieldids.
	 * @return array - List of <\App\Modules\Settings\Base\Models\MenuItem> instances
	 */
	public static function getPinnedItems($fieldList = [])
	{
		$skipMenuItemList = ['LBL_AUDIT_TRAIL', 'LBL_SYSTEM_INFO', 'LBL_PROXY_SETTINGS', 'LBL_DEFAULT_MODULE_VIEW',
			'LBL_FIELDFORMULAS', 'LBL_FIELDS_ACCESS', 'LBL_MAIL_MERGE', 'NOTIFICATIONSCHEDULERS',
			'INVENTORYNOTIFICATION', 'ModTracker', 'LBL_WORKFLOW_LIST', 'LBL_TOOLTIP_MANAGEMENT'];
		$query = (new \App\Db\Query())->from(self::$itemsTable)
			->where(['pinned' => 1, 'active' => 0]);
		if (!empty($fieldList)) {
			$query->andWhere([self::$itemsId => $fieldList]);
		}
		$dataReader = $query->andWhere(['NOT IN', 'name', $skipMenuItemList])
				->createCommand()->query();
		$menuItemModels = [];
		while ($rowData = $dataReader->read()) {
			$menuItem = \App\Modules\Settings\Base\Models\MenuItem::getInstanceFromArray($rowData);
			$menuItem->setMenu($rowData['blockid']);
			$menuItemModels[$rowData[self::$itemId]] = $menuItem;
		}
		return $menuItemModels;
	}

	/**
	 * Function to get name module
	 * @return type module name
	 */
	public function getModule()
	{
		$urlParams = \vtlib\Functions::getQueryParams($this->getUrl());
		return $urlParams['module'];
	}
}
