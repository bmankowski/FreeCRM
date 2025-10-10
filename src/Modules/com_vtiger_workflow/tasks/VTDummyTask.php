<?php

namespace FreeCRM\Modules\com_vtiger_workflow\tasks;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class VTDummyTask extends VTTask
{

	public $executeImmediately = true;

	public function getFieldNames()
	{
		return array();
	}

	/**
	 * Execute task
	 * @param \FreeCRM\Modules\Vtiger\Models\Record $recordModel
	 */
	public function doTask($recordModel)
	{
		$statement = $this->statement;
		echo "This is a dummy workflow task with $statement";
	}
}
