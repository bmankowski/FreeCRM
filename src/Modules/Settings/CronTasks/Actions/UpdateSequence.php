<?php

namespace FreeCRM\Modules\Settings\CronTasks\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

use FreeCRM\Modules\Settings\CronTasks\Models\Module as Settings_CronTasks_Module_Model;
class UpdateSequence extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$sequencesList = $request->get('sequencesList');

		$moduleModel = Settings_CronTasks_Module_Model::getInstance($qualifiedModuleName);

		$response = new \FreeCRM\Http\Vtiger_Response();
		if ($sequencesList) {
			$moduleModel->updateSequence($sequencesList);
			$response->setResult(array(true));
		} else {
			$response->setError();
		}

		$response->emit();
	}
}
