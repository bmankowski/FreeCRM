<?php

namespace App\Modules\Settings\Roles\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/*
 * Settings Module Model Class
 */

class Module extends \App\Modules\Settings\Base\Models\Module
{

	public $baseTable = 'vtiger_role';
	public $baseIndex = 'roleid';
	public $listFields = array('roleid' => 'Role Id', 'rolename' => 'Name');
	public $name = 'Roles';

	/**
	 * Function to get the url for default view of the module
	 * @return string - url
	 */
	public function getDefaultUrl()
	{
		return 'index.php?module=Roles&parent=Settings&view=Index';
	}

	/**
	 * Function to get the url for Create view of the module
	 * @return string - url
	 */
	public function getCreateRecordUrl()
	{
		return 'index.php?module=Roles&parent=Settings&view=Index';
	}
}
