<?php

namespace App\Modules\Settings\Base\Models;
use App\Modules\Settings\Base\Models\Menu;
use App\Modules\Settings\Base\Models\MenuItem;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Settings Module Model Class
 */
class Module extends \App\Modules\Base\Models\Record
{

	public $baseTable = 'vtiger_settings_field';
	public $baseIndex = 'fieldid';
	public $listFields = array('name' => 'Name', 'description' => 'Description');
	protected array $virtualListFields = ['actions'];
	protected ?array $listFieldModels = null;
	public $nameFields = array('name');
	public $name = 'Vtiger';

	public function getName($includeParentIfExists = false)
	{
		if ($includeParentIfExists) {
			return $this->getParentName() . ':' . $this->name;
		}
		return $this->name;
	}

	public function getParentName()
	{
		return 'Settings';
	}

	public function getBaseTable()
	{
		return $this->baseTable;
	}

	public function getBaseIndex()
	{
		return $this->baseIndex;
	}

	public function setListFields($fieldNames)
	{
		$this->listFields = $fieldNames;
		return $this;
	}

	public function getListFields(): array
	{
		if ($this->listFieldModels === null) {
			$fields = $this->listFields;
			$fieldObjects = array();
			foreach ($fields as $fieldName => $fieldLabel) {
				$fieldObjects[$fieldName] = new \App\Runtime\BaseModel(array('name' => $fieldName, 'label' => $fieldLabel));
			}
			$this->listFieldModels = $fieldObjects;
		}
		return $this->listFieldModels;
	}

	public function getQueryableListFields(): array
	{
		return array_values(array_diff(array_keys($this->listFields), $this->virtualListFields));
	}

	public function isVirtualListField(string $fieldName): bool
	{
		return in_array($fieldName, $this->virtualListFields, true);
	}

	/**
	 * Function to get name fields of this module
	 * @return array list field names
	 */
	public function getNameFields()
	{
		return $this->nameFields;
	}

	/**
	 * Function to get field using field name
	 * @param string $fieldName
	 * @return mixed
	 */
	public function getField($fieldName)
	{
		return new \App\Runtime\BaseModel(array('name' => $fieldName, 'label' => $fieldName));
	}

	public function hasCreatePermissions()
	{
		return true;
	}

	/**
	 * Function to get all the Settings menus
	 * @param \App\Http\Vtiger_Request $request Optional request for user context and caching
	 * @return array - List of \App\Modules\Settings\Base\Models\Menu instances
	 */
	public function getMenus($request = null)
	{
		return \App\Modules\Settings\Base\Models\Menu::getAllForUser(
			$request && $request->hasUser() ? $request->getUserId() : null,
			$request
		);
	}

	/**
	 * Function to get all the Settings menu items for the given menu
	 * @return array - List of \App\Modules\Settings\Base\Models\MenuItem instances
	 */
	public function getMenuItems($menu = false)
	{
		$menuModel = false;
		if ($menu) {
			$menuModel = \App\Modules\Settings\Base\Models\Menu::getInstance($menu);
		}
		return \App\Modules\Settings\Base\Models\MenuItem::getAll($menuModel);
	}

	public function isPagingSupported()
	{
		return true;
	}

	/**
	 * Function to get the instance of Settings module model
	 * @return \App\Modules\Settings\Base\Models\Module instance
	 */
	public static function getInstance($name = 'Settings:Vtiger')
	{
		// For Settings:Vtiger, return instance of this class
		if ($name === 'Settings:Vtiger') {
			return new self();
		}
		$modelClassName = \App\Core\Loader::getComponentClassName('Model', 'Module', $name);
		// Ensure class name is resolved from global namespace
		if ($modelClassName[0] !== '\\') {
			$modelClassName = '\\' . $modelClassName;
		}
		return new $modelClassName();
	}

	/**
	 * Function to get Index view Url
	 * @return string URL
	 */
	public function getIndexViewUrl()
	{
		return 'index.php?module=' . $this->getName() . '&parent=' . $this->getParentName() . '&view=Index';
	}

	public function prepareMenuToDisplay($menuModels, $moduleName, $selectedMenuId, $fieldId)
	{
		if (!empty($selectedMenuId)) {
			$selectedMenu = \App\Modules\Settings\Base\Models\Menu::getInstanceById($selectedMenuId);
		} elseif (!empty($moduleName) && $moduleName != 'Vtiger') {
			$fieldItem = \App\Modules\Settings\Base\Views\Index::getSelectedFieldFromModule($menuModels, $moduleName);
			if ($fieldItem) {
				$selectedMenu = \App\Modules\Settings\Base\Models\Menu::getInstanceById($fieldItem->get('blockid'));
				$fieldId = $fieldItem->get('fieldid');
			} else {
				reset($menuModels);
				$firstKey = key($menuModels);
				$selectedMenu = $menuModels[$firstKey];
			}
		} else {
			$selectedMenu = false;
		}

		$menu = [];
		foreach ($menuModels as $blockId => $menuModel) {
			if ($menuModel->getType() != 1) {
				$childs = [];
				foreach ($menuModel->getMenuItems() as $menuItem) {
					if ($menuItem->getId() == $fieldId) {
						$this->set('selected', $menuItem);
					}
					$childs[] = [
						'id' => $menuItem->getId(),
						'active' => $menuItem->getId() == $fieldId ? true : false,
						'name' => $menuItem->get('name'),
						'type' => 'Shortcut',
						'sequence' => $menuModel->get('sequence'),
						'newwindow' => '0',
						'icon' => $menuItem->get('iconpath'),
						'dataurl' => $menuItem->getUrl(),
						'parent' => 'Settings',
						'moduleName' => \App\Modules\Base\Models\Menu::getModuleNameFromUrl($menuItem->getUrl()),
					];
				}
				$menu[] = [
					'id' => $blockId,
					'active' => ($selectedMenu && $selectedMenu->get('blockid') == $blockId) ? true : false,
					'name' => $menuModel->getLabel(),
					'type' => 'Label',
					'sequence' => $menuModel->get('sequence'),
					'childs' => $childs,
					'icon' => $menuModel->get('icon'),
					'moduleName' => 'Settings::Vtiger',
				];
			} else {
				$menu[] = [
					'id' => $blockId,
					'active' => ($selectedMenu && $selectedMenu->get('blockid') == $blockId) ? true : false,
					'name' => $menuModel->getLabel(),
					'type' => 'Shortcut',
					'sequence' => $menuModel->get('sequence'),
					'newwindow' => '0',
					'icon' => $menuModel->get('icon'),
					'dataurl' => $menuModel->get('linkto'),
					'moduleName' => 'Settings::Vtiger',
				];
			}
		}
		return $menu;
	}

	public static function addSettingsField($block, $params)
	{
		$db = \App\Db\Db::getInstance();
		$blockId = \vtlib\Deprecated::getSettingsBlockId($block);
		$sequence = (new \App\Db\Query())->from('vtiger_settings_field')->where(['blockid' => $blockId])
			->max('sequence');
		$params['blockid'] = $blockId;
		$params['sequence'] = $sequence;
		$db->createCommand()->insert('vtiger_settings_field', $params)->execute();
	}

	public static function deleteSettingsField($block, $name)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$blockId = \vtlib\Deprecated::getSettingsBlockId($block);
		$db->delete('vtiger_settings_field', 'name = ? && blockid=?', [$name, $blockId]);
	}

	/**
	 * Delete settings field by module name
	 * @param string $moduleName
	 */
	public static function deleteSettingsFieldBymodule($moduleName)
	{
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->delete('vtiger_settings_field', ['like', 'linkto', "module={$moduleName}&"])->execute();
	}
}
