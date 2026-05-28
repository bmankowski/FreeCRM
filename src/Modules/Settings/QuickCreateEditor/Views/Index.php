<?php

namespace App\Modules\Settings\QuickCreateEditor\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function __construct()
	{
		$this->exposeMethod('showFieldLayout');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if ($this->isMethodExposed($mode)) {
			$this->invokeExposedMethod($mode, $request);
		} else {
			//by default show field layout
			$this->showFieldLayout($request);
		}
	}

	public function showFieldLayout(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		$menuModelsList = \App\Modules\Base\Models\Module::getQuickCreateModules();

		if (empty($sourceModule)) {
			//To get the first element
			$firstElement = reset($menuModelsList);
			$sourceModule = array($firstElement->get('name'));
		} else
			$sourceModule = array($sourceModule);

		$quickCreateContents = array();

		if (in_array('Calendar', $sourceModule))
			$sourceModule = array('Calendar', 'Events');

		foreach ($sourceModule as $module) {
			$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($module);

			$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_QUICKCREATE);
			$quickCreateContents[$module] = $recordStructureInstance->getStructure();
		}

		$qualifiedModule = $request->getModule(false);

		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule[0]);
		$viewer->assign('SUPPORTED_MODULES', $menuModelsList);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('RECORDS_STRUCTURE', $quickCreateContents);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);

		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModule);
		} else {
			$viewer->view('IndexView.tpl', $qualifiedModule);
		}
	}
}
