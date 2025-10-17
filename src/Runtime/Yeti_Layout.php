<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


namespace App\Runtime;

use App\Http\Vtiger_Session;
use App\AppConfig;
use App\Vtiger_Loader;
use App\database\PearDatabase;

class Yeti_Layout
{

	public static function getActiveLayout()
	{
		$layout = Vtiger_Session::get('layout');
		if (!empty($layout)) {
			return $layout;
		}

		return \App\AppConfig::main('defaultLayout');
	}

	public static function getLayoutFile(string $name)
	{
		$basePath = 'layouts/' . \App\AppConfig::main('defaultLayout') . '/';
		$filePath = Vtiger_Loader::resolveNameToPath('~' . $basePath . $name);
		if (is_file($filePath)) {
			return $basePath . $name;
		}

		$basePath = 'layouts/' . CRM_Viewer::getDefaultLayoutName() . '/';
		return $basePath . $name;
	}

	public static function getAllLayouts()
	{
		$db = \App\database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT name,label FROM vtiger_layout');
		$folders = [
			'basic' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_DEFAULT')
		];
		while ($row = $db->fetch_array($result)) {
			$folders[$row['name']] = \App\Runtime\Vtiger_Language_Handler::translate($row['label']);
		}

		return $folders;
	}
}
