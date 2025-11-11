<?php

namespace App\Modules\Settings\DataAccess\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Condition extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$baseModule = $request->get('base_module');
		$num = $request->get('num');
		if ("" == $num) {
			$num = 0;
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('NUM', ++$num);
		$viewer->assign('BASE_MODULE', $baseModule);
		$viewer->assign('FIELD_LIST', \App\Modules\Settings\DataAccess\Models\Module::getListBaseModuleField($baseModule));
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		
		// Prepare DataAccess Condition-specific data for Condition template
		$this->prepareDataAccessConditionData($viewer);
		
		echo $viewer->view('Condition.tpl', $qualifiedModuleName, true);
	}
	
	/**
	 * Prepare data for DataAccess Condition template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareDataAccessConditionData($viewer)
	{
		$fieldList = $viewer->getTemplateVars('FIELD_LIST');
		$fieldInfoJson = [];
		$conditionListsByType = [];
		foreach ($fieldList as $moduleName => $fields) {
			foreach ($fields as $field) {
				$fieldInfoJson[$moduleName][$field['name']] = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode($field['info']));
				// Prepare condition list for this field type
				$fieldType = $field['info']['type'];
				if (!isset($conditionListsByType[$fieldType])) {
					$conditionListsByType[$fieldType] = \App\Modules\Settings\DataAccess\Models\Module::getConditionByType($fieldType);
				}
			}
		}
		$viewer->assign('FIELD_INFO_JSON', $fieldInfoJson);
		$viewer->assign('CONDITION_LISTS_BY_TYPE', $conditionListsByType);
	}
}
