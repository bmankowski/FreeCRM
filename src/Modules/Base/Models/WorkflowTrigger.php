<?php

namespace App\Modules\Base\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class WorkflowTrigger {

	/**
	 * Function executes workflow tasks
	 * @param string $moduleName
	 * @param int $record
	 * @param array $ids
	 * @param int $userId
	 */
	public static function execute($moduleName, $record, $ids, $userId)
	{
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
		if ($userId) {
			$recordModel->executeUser = $userId;
		}
		$wfs = new \App\Modules\Workflow\VTWorkflowManager();
		foreach ($ids as $id) {
			$workflow = $wfs->retrieve($id);
			if ($workflow->evaluate($recordModel)) {
				$workflow->performTasks($recordModel);
			}
		}
	}
}
