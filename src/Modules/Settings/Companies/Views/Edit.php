<?php

namespace FreeCRM\Modules\Settings\Companies\Views;



/**
 * Companies edit view class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Modules\Settings\Companies\Models\Record as Settings_Companies_Record_Model;
class Edit extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	/**
	 * Process function
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$record = $request->get('record');

		if ($record) {
			$recordModel = Settings_Companies_Record_Model::getInstance($record);
		} else {
			$recordModel = new Settings_Companies_Record_Model();
		}
		$viewer->assign('COMPANY_COLUMNS', \FreeCRM\Modules\Settings\Companies\Models\Module::getColumnNames());
		$viewer->assign('INDUSTRY_LIST', \FreeCRM\Modules\Settings\Companies\Models\Module::getIndustryList());
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('MODULE', $moduleName);
		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}

	/**
	 * Get footer JS scripts
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @return Vtiger_JsScript_Model[]
	 */
	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$jsFileNames = [
			'modules.Vtiger.resources.AdvanceFilterEx',
			'modules.Settings.Companies.resources.Edit',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
