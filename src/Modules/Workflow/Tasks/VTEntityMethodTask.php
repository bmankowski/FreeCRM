<?php

namespace App\Modules\Workflow\Tasks;

use App\Modules\Workflow\VTTask;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class VTEntityMethodTask extends VTTask
{

	public $executeImmediately = true;
	public $methodName;

	public function getFieldNames(): array
	{
		return ['methodName'];
	}

	/**
	 * Execute task
	 * @param \App\Modules\Base\Models\Record $recordModel
	 */
	public function doTask($recordModel, ?\App\Modules\Workflow\RelationWorkflowContext $context = null)
	{
		(new VTEntityMethodManager())->executeMethod($recordModel, $this->methodName);
	}
}
