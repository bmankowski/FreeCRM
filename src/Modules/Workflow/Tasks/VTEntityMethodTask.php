<?php

namespace App\Modules\Workflow\Tasks;
\nuse App\Modules\Workflow\VTTask;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */
require_once('src/Modules/Workflow/VTEntityMethodManager.php');

class VTEntityMethodTask extends VTTask
{

	public $executeImmediately = true;

	public function getFieldNames(): array
	{
		return ['methodName'];
	}

	/**
	 * Execute task
	 * @param \App\Modules\Vtiger\Models\Record $recordModel
	 */
	public function doTask($recordModel)
	{
		(new VTEntityMethodManager())->executeMethod($recordModel, $this->methodName);
	}
}
