<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


namespace FreeCRM\Runtime;

use FreeCRM\Http\Vtiger_Session;
use FreeCRM\AppConfig;
use FreeCRM\Vtiger_Loader;
use FreeCRM\database\PearDatabase;

class Yeti_Layout
{

	public static function getActiveLayout()
	{
		$layout = Vtiger_Session::get('layout');
		if (!empty($layout)) {
			return $layout;
		}

		return AppConfig::main('defaultLayout');
	}

	public static function getLayoutFile(string $name)
	{
		$basePath = 'layouts/' . AppConfig::main('defaultLayout') . '/';
		$filePath = Vtiger_Loader::resolveNameToPath('~' . $basePath . $name);
		if (is_file($filePath)) {
			return $basePath . $name;
		}

		$basePath = 'layouts/' . FreeCRM_Viewer::getDefaultLayoutName() . '/';
		return $basePath . $name;
	}

	public static function getAllLayouts()
	{
		$db = \FreeCRM\database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT name,label FROM vtiger_layout');
		$folders = [
			'basic' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_DEFAULT')
		];
		while ($row = $db->fetch_array($result)) {
			$folders[$row['name']] = \FreeCRM\Runtime\Vtiger_Language_Handler::translate($row['label']);
		}

		return $folders;
	}
}
