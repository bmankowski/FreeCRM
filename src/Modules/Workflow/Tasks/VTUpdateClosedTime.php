<?php

namespace App\Modules\Workflow\Tasks;

use App\Modules\Workflow\VTTask;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class VTUpdateClosedTime extends VTTask
{

	public $executeImmediately = true;

	public function getFieldNames(): array
	{
		return [];
	}

	/**
	 * Execute task
	 * @param \App\Modules\Base\Models\Record $recordModel
	 */
	public function doTask($recordModel)
	{
		\App\Db\Db::getInstance()->createCommand()->update('vtiger_crmentity', ['closedtime' => date('Y-m-d H:i:s')], ['crmid' => $recordModel->getId()])->execute();
	}
}
