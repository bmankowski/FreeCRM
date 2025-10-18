<?php

namespace App\Modules\RecycleBin;

/* +********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***************************************************************************** */

class RecycleBin {

	/**
	 * Invoked when special actions are performed on the module.
	 * @param string Module name
	 * @param string Event Type
	 */
	public function vtlib_handler($moduleName, $eventType)
	{

		$adb = \App\Database\database\PearDatabase::getInstance();

		if ($eventType == 'module.postinstall') {
			// Mark the module as Standard module
			$adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', array($moduleName));
		} else if ($eventType == 'module.disabled') {

		} else if ($eventType == 'module.enabled') {

		} else if ($eventType == 'module.preuninstall') {

		} else if ($eventType == 'module.preupdate') {

		} else if ($eventType == 'module.postupdate') {

		}
	}
}
