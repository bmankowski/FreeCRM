<?php

namespace App\Modules\Settings\AutomaticAssignment\Views;



/**
 * Automatic assignment edit view
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class Edit extends \App\Modules\Settings\Vtiger\Views\Index
{

	/**
	 * Checking permission 
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \Exception\NoPermittedForAdmin
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\User::getCurrentUserModel();
		if (!$currentUserModel->isAdmin() || empty($request->get('record'))) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = \App\Modules\Settings\AutomaticAssignment\Models\Record::getInstanceById($request->get('record'));
		$sourceModuleName = $recordModel->getSourceModuleName();
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('SOURCE_MODULE', $sourceModuleName);

		if ($request->has('tab')) {
			$viewer->assign('FIELD_NAME', $request->get('tab'));
			$viewer->assign('LABEL', $recordModel->getEditFields()[$request->get('tab')]);
			$viewer->view('Tab.tpl', $qualifiedModuleName);
		} else {
			$this->getVariablesToAdvancedFilter($viewer, $recordModel);
			$viewer->view('Edit.tpl', $qualifiedModuleName);
		}
	}

	/**
	 * Function gets variables to advanced filter
	 * @param CRM_Viewer $viewer
	 * @param \App\Modules\Settings\AutomaticAssignment\Models\Record $recordModel
	 */
	private function getVariablesToAdvancedFilter(CRM_Viewer $viewer, $recordModel)
	{
		$sourceModuleName = $recordModel->getSourceModuleName();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($recordModel->get('tabid'));
		$recordStructureInstance = \App\Modules\Vtiger\Models\RecordStructure::getInstanceForModule($moduleModel);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());

		$conditions = $recordModel->get('conditions');
		if ($conditions) {
			$conditions = \App\Json::decode($conditions);
		}
		$criteria = \Vtiger_AdvancedFilter_Helper::transformToAdvancedFilterCondition($conditions);
		$viewer->assign('ADVANCE_CRITERIA', \Vtiger_AdvancedFilter_Helper::transformToAdvancedFilterCondition($conditions));

		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('DATE_FILTERS', \Vtiger_AdvancedFilter_Helper::getDateFilter($sourceModuleName));

		if ($sourceModuleName === 'Calendar') {
			$advanceFilterOpsByFieldType = \App\Modules\Calendar\Models\Field::getAdvancedFilterOpsByFieldType();
		} else {
			$advanceFilterOpsByFieldType = \App\Modules\Vtiger\Models\Field::getAdvancedFilterOpsByFieldType();
		}
		$viewer->assign('ADVANCED_FILTER_OPTIONS', \App\CustomView::ADVANCED_FILTER_OPTIONS);
		$viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', $advanceFilterOpsByFieldType);
	}

	/**
	 * Scripts
	 * @param \App\Http\Vtiger_Request $request
	 * @return Vtiger_JsScript_Model[]
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = [
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.Edit",
			'modules.CustomView.resources.CustomView'
		];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
