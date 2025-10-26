<?php

namespace App\Modules\Settings\Companies\Views;



/**
 * Companies edit view class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Edit extends \App\Modules\Settings\Base\Views\Index
{

	/**
	 * Process function
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$record = $request->get('record');

		if ($record) {
			$recordModel = \App\Modules\Settings\Companies\Models\Record::getInstance($record);
		} else {
			$recordModel = new \App\Modules\Settings\Companies\Models\Record();
		}
		$viewer->assign('COMPANY_COLUMNS', \App\Modules\Settings\Companies\Models\Module::getColumnNames());
		$viewer->assign('INDUSTRY_LIST', \App\Modules\Settings\Companies\Models\Module::getIndustryList());
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}

	/**
	 * Get footer JS scripts
	 * @param \App\Http\Vtiger_Request $request
	 * @return \App\View\Assets\ScriptAsset[]
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$jsFileNames = [
			'modules.Base.resources.AdvanceFilterEx',
			'modules.Settings.Companies.resources.Edit',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
