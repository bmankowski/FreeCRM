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

Class \Settings_DataAccess_Step2_View extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$moduleName = $request->getModule();
		$baseModule = $request->get('base_module');
		$idTpl = $request->get('tpl_id');
		$viewer = $this->getViewer($request);

		if ($idTpl && $baseModule != 'All') {
			$docInfo = \App\Modules\Settings\DataAccess\Models\Module::getDataAccessInfo($idTpl);
			$viewer->assign('BASE_INFO', $docInfo['basic_info']);
			$countRequiredConditions = count($docInfo['required_conditions']);
			for ($i = 0; $i < $countRequiredConditions; $i++) {
				$fieldModel = \App\Modules\Vtiger\Models\Field::getInstance($docInfo['required_conditions'][$i]['fieldname'], \App\Modules\Vtiger\Models\Module::getInstance($baseModule));
				$docInfo['required_conditions'][$i]['info'] = $fieldModel->getFieldInfo();
			}

			$viewer->assign('REQUIRED_CONDITIONS', $docInfo['required_conditions']);

			$countOptionalConditions = count($docInfo['optional_conditions']);
			for ($i = 0; $i < $countOptionalConditions; $i++) {

				$fieldModel = \App\Modules\Vtiger\Models\Field::getInstance($docInfo['optional_conditions'][$i]['fieldname'], \App\Modules\Vtiger\Models\Module::getInstance($baseModule));
				$docInfo['optional_conditions'][$i]['info'] = $fieldModel->getFieldInfo();
			}
			$viewer->assign('OPTIONAL_CONDITIONS', $docInfo['optional_conditions']);
			$viewer->assign('TPL_ID', $idTpl);
		}
		$viewer->assign('STEP', 2);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('REQUEST', $request);
		$viewer->assign('SUMMARY', $request->get('summary'));
		$viewer->assign('BASE_MODULE', $baseModule);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('FIELD_LIST', \App\Modules\Settings\DataAccess\Models\Module::getListBaseModuleField($baseModule));
		$viewer->assign('CONDITION_BY_TYPE', \App\Modules\Settings\DataAccess\Models\Module::getConditionByType());

		echo $viewer->view('Step2.tpl', $qualifiedModuleName, true);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.Conditions"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
