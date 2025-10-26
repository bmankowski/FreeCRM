<?php

namespace App\Modules\Settings\PickListDependency\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Index extends \App\Modules\Settings\Base\Actions\Basic
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('checkCyclicDependency');
	}

	public function checkCyclicDependency(\App\Http\Vtiger_Request $request)
	{
		$module = $request->get('sourceModule');
		$sourceField = $request->get('sourcefield');
		$targetField = $request->get('targetfield');
		$result = \App\Modules\PickList\DependencyPicklist::checkCyclicDependency($module, $sourceField, $targetField);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array('result' => $result));
		$response->emit();
	}
}
