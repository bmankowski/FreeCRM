<?php

namespace App\Modules\Settings\Vtiger\Models;
use App\Modules\Settings\Vtiger\Models\Menu;
use App\Modules\Settings\Vtiger\Models\MenuItem;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/*
 * Settings Menu Model Class
 */

class Menu extends \App\Modules\Vtiger\Models\Record
{

	protected static $menusTable = 'vtiger_settings_blocks';
	protected static $menuId = 'blockid';
	protected static $casheMenu = false;

	/**
	 * Function to get the Id of the Menu Model
	 * @return int - Menu Id
	 */
	public function getId()
	{
		return $this->get(self::$menuId);
	}

	/**
	 * Function to get the menu label
	 * @return string - Menu Label
	 */
	public function getLabel()
	{
		return $this->get('label');
	}

	/**
	 * Function to get the menu type
	 * @return string - Menu Label
	 */
	public function getType()
	{
		return $this->get('type');
	}

	/**
	 * Function to get the url to get to the Settings Menu Block
	 * @return string - Menu Item landing url
	 */
	public function getUrl()
	{
		$url = $this->get('linkto');
		$url = \App\Utils\ListViewUtils::decodeHtml($url);
		$url .= '&block=' . $this->getId();
		return $url;
	}

	/**
	 * Function to get the url to list the items of the Menu
	 * @return string - List url
	 */
	public function getListUrl()
	{
		return 'index.php?module=Vtiger&parent=Settings&view=ListMenu&block=' . $this->getId();
	}

	/**
	 * Function to get all the menu items of the current menu
	 * @return array - List of \App\Modules\Settings\Vtiger\Models\MenuItem instances
	 */
	public function getItems()
	{
		return \App\Modules\Settings\Vtiger\Models\MenuItem::getAll($this);
	}

	/**
	 * Static function to get the list of all the Settings Menus
	 * @return array - List of \App\Modules\Settings\Vtiger\Models\Menu instances
	 */
	public static function getAll()
	{
		if (self::$casheMenu) {
			return self::$casheMenu;
		}
		$dataReader = (new \App\Db\Query())->from(self::$menusTable)->where(['or', ['like', 'admin_access', ',' . \App\User::getCurrentUserId() . ','], ['admin_access' => null]])
				->orderBy(['sequence' => SORT_ASC])
				->createCommand()->query();
		$menuModels = [];
		while ($row = $dataReader->read()) {
			$blockId = $row[self::$menuId];
			$menuModels[$blockId] = \App\Modules\Settings\Vtiger\Models\Menu::getInstanceFromArray($row);
		}
		self::$casheMenu = $menuModels;
		return $menuModels;
	}

	/**
	 * Static Function to get the instance of Settings Menu model with the given value map array
	 * @param array $valueMap
	 * @return <\App\Modules\Settings\Vtiger\Models\Menu> instance
	 */
	public static function getInstanceFromArray($valueMap)
	{
		return new self($valueMap);
	}

	/**
	 * Array with instances, kay as number id element of menu
	 * @var array 
	 */
	static $cacheInstance = [];

	/**
	 * Static Function to get the instance of Settings Menu model for given menu id
	 * @param int $id - Menu Id
	 * @return <\App\Modules\Settings\Vtiger\Models\Menu> instance
	 */
	public static function getInstanceById($id, $module = null)
	{
		if (isset(self::$cacheInstance[$id])) {
			return self::$cacheInstance[$id];
		}
		$db = \App\Database\PearDatabase::getInstance();

		$sql = sprintf('SELECT * FROM %s WHERE %s = ?', self::$menusTable, self::$menuId);
		$params = [$id];

		$result = $db->pquery($sql, $params);

		if ($db->num_rows($result) > 0) {
			$rowData = $db->query_result_rowdata($result, 0);
			if ($rowData && is_array($rowData)) {
				$instance = \App\Modules\Settings\Vtiger\Models\Menu::getInstanceFromArray($rowData);
				self::$cacheInstance[$id] = $instance;
				return $instance;
			}
		}
		self::$cacheInstance[$id] = false;
		return false;
	}

	/**
	 * Static Function to get the instance of Settings Menu model for the given menu name
	 * @param string $name - Menu Name
	 * @return <\App\Modules\Settings\Vtiger\Models\Menu> instance
	 */
	public static function getInstance($name)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$sql = sprintf('SELECT * FROM %s WHERE label = ?', self::$menusTable);
		$params = [$name];

		$result = $db->pquery($sql, $params);

		if ($db->num_rows($result) > 0) {
			$rowData = $db->query_result_rowdata($result, 0);
			return \App\Modules\Settings\Vtiger\Models\Menu::getInstanceFromArray($rowData);
		}
		return false;
	}

	/**
	 * Function returns menu items for the current menu
	 * @return <\App\Modules\Settings\Vtiger\Models\MenuItem>
	 */
	public function getMenuItems()
	{
		return \App\Modules\Settings\Vtiger\Models\MenuItem::getAll($this);
	}
}
