<?php

namespace App\Modules\Vtiger\Models;
use App\Modules\Settings\Vtiger\Models\MenuItem;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************************************************************** */


use App\Runtime\Vtiger_Language_Handler;
class Menu {

	/**
	 * Static Function to get all the accessible menu models with/without ordering them by sequence
	 * @param boolean $sequenced - true/false
	 * @return <Array> - List of \App\Modules\Vtiger\Models\Menu instances
	 */
	public static function getAll($sequenced = false, $restrictedModulesList = [])
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$userPrivModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();

		$roleMenu = 'user_privileges/menu_' . filter_var($userPrivModel->get('roleid'), FILTER_SANITIZE_NUMBER_INT) . '.php';
		if (file_exists($roleMenu)) {
			require($roleMenu);
		} else {
			require('user_privileges/menu_0.php');
		}
		if (count($menus) == 0) {
			require('user_privileges/menu_0.php');
		}
		return $menus;
	}

	/**
	 * Get breadcrumbs
	 * @deprecated This method is deprecated. Breadcrumbs are now built in controllers via buildBreadcrumbs()
	 * @param mixed $pageTitle
	 * @return array
	 */
	public static function getBreadcrumbs($pageTitle = false)
	{
		// This method is deprecated - breadcrumbs should be built in controllers
		// Kept for backward compatibility only
		return [];
	}

	public static function getParentMenu($parentList, $parent, $module, $return = [])
	{
		if ($parent != 0 && key_exists($parent, $parentList)) {
			$return [] = [
				'name' => \App\Runtime\Vtiger_Language_Handler::translate($parentList[$parent]['name'], $module),
				'url' => $parentList[$parent]['url'],
			];
			if ($parentList[$parent]['parent'] != 0 && key_exists($parentList[$parent]['parent'], $parentList)) {
				$return = self::getParentMenu($parentList, $parentList[$parent]['parent'], $module, $return);
			}
		}
		return $return;
	}

	/**
	 * 
	 * @param type $url
	 * @return type modulename 
	 */
	public static function getModuleNameFromUrl($url)
	{
		$params = \vtlib\Functions::getQueryParams($url);
		if ($params['parent']) {
			return ($params['parent'] . ':' . $params['module']);
		}
		return $params['module'];
	}

	public static function getMenuIcon($menu, $title = '')
	{
		if ($title == '') {
			$title = \App\Runtime\Vtiger_Language_Handler::translate($menu['label']);
		}
		if (is_string($menu)) {
			$iconName = vimage_path($menu);
			if (file_exists($iconName)) {
				return '<img src="' . $iconName . '" alt="' . $title . '" title="' . $title . '" class="menuIcon" />';
			}
		}

		if (!empty($menu['icon'])) {
			if (strpos($menu['icon'], 'glyphicon-') !== false) {
				return '<span class="glyphicon ' . $menu['icon'] . '" aria-hidden="true"></span>';
			} elseif (strpos($menu['icon'], 'fa-') !== false) {
				return '<span class="' . $menu['icon'] . '" aria-hidden="true"></span>';
			} elseif (strpos($menu['icon'], 'adminIcon-') !== false || strpos($menu['icon'], 'userIcon-') !== false || strpos($menu['icon'], 'AdditionalIcon-') !== false) {
				return '<span class="menuIcon ' . $menu['icon'] . '" aria-hidden="true"></span>';
			}

			$icon = vimage_path($menu['icon']);
			if (file_exists($icon)) {
				return '<img src="' . $icon . '" alt="' . $title . '" title="' . $title . '" class="menuIcon" />';
			}
		}
		if (isset($menu['type']) && $menu['type'] == 'Module') {
			return '<span class="menuIcon userIcon-' . $menu['mod'] . '" aria-hidden="true"></span>';
		}
		return '';
	}
}
