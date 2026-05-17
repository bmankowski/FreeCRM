<?php

namespace App\Modules\Settings\Base\Models;


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

class Menu extends \App\Modules\Base\Models\Record
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
		return 'index.php?module=Vtiger&parent=Settings&view=ListViewMenu&block=' . $this->getId();
	}

	/**
	 * Function to get all the menu items of the current menu
	 * @return array - List of \App\Modules\Settings\Base\Models\MenuItem instances
	 */
	public function getItems()
	{
		return \App\Modules\Settings\Base\Models\MenuItem::getAll($this);
	}

	/**
	 * Get all menus for specified user
	 * @param int $userId User ID (if null, tries to get from current session)
	 * @param \App\Http\Vtiger_Request $request Optional request for caching
	 * @return array Menu models
	 */
	public static function getAllForUser($userId = null, $request = null)
	{
		// Backward compatibility: get user ID from session if not provided
		if ($userId === null) {
			if ($request && $request->hasUser()) {
				$userId = $request->getUserId();
			} else {
				$userId = (int) (\App\User\CurrentUser::getId() ?? 0);
			}
		}
		
		$cacheKey = "menu_all_user_{$userId}";
		
		// Try request cache first
		if ($request && $request->hasCached($cacheKey)) {
			return $request->getCached($cacheKey);
		}
		
		// Try static cache (for backward compatibility during transition)
		if (is_array(self::$casheMenu) && isset(self::$casheMenu[$userId])) {
			return self::$casheMenu[$userId];
		}
		
		$dataReader = (new \App\Db\Query())->from(self::$menusTable)
			->where(['or', 
				['like', 'admin_access', ',' . $userId . ','], 
				['admin_access' => null]])
			->orderBy(['sequence' => SORT_ASC])
			->createCommand()->query();
		
		$menuModels = [];
		while ($row = $dataReader->read()) {
			$blockId = $row[self::$menuId];
			$menuModels[$blockId] = self::getInstanceFromArray($row);
		}
		
		// Cache results
		if (!is_array(self::$casheMenu)) {
			self::$casheMenu = [];
		}
		self::$casheMenu[$userId] = $menuModels;
		if ($request) {
			$request->setCached($cacheKey, $menuModels);
		}
		
		return $menuModels;
	}

	/**
	 * Static function to get the list of all the Settings Menus
	 * @deprecated Use getAllForUser() instead
	 * @return array - List of \App\Modules\Settings\Base\Models\Menu instances
	 */
	public static function getAll()
	{
		return self::getAllForUser();
	}

	/**
	 * Static Function to get the instance of Settings Menu model with the given value map array
	 * @param array $valueMap
	 * @return <\App\Modules\Settings\Base\Models\Menu> instance
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
	 * @return <\App\Modules\Settings\Base\Models\Menu> instance
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
				$instance = \App\Modules\Settings\Base\Models\Menu::getInstanceFromArray($rowData);
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
	 * @return <\App\Modules\Settings\Base\Models\Menu> instance
	 */
	public static function getInstance($name)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$sql = sprintf('SELECT * FROM %s WHERE label = ?', self::$menusTable);
		$params = [$name];

		$result = $db->pquery($sql, $params);

		if ($db->num_rows($result) > 0) {
			$rowData = $db->query_result_rowdata($result, 0);
			return \App\Modules\Settings\Base\Models\Menu::getInstanceFromArray($rowData);
		}
		return false;
	}

	/**
	 * Function returns menu items for the current menu
	 * @return <\App\Modules\Settings\Base\Models\MenuItem>
	 */
	public function getMenuItems()
	{
		return \App\Modules\Settings\Base\Models\MenuItem::getAll($this);
	}
}
