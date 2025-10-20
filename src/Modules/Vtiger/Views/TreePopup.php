<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */



namespace App\Modules\Vtiger\Views;

use App\Http\Vtiger_Request;

class TreePopup  extends \App\Modules\Vtiger\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPrivilegesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function returns the module name for which the popup should be initialized
	 * @param Vtiger_request $request
	 * @return string
	 */
	public function getModule(Vtiger_request $request)
	{
		$moduleName = $request->getModule();
		return $moduleName;
	}

	/**
	 * Tree in popup
	 * @param Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $this->getModule($request);
		$template = $request->get('template');
		$srcField = $request->get('src_field');
		$srcRecord = $request->get('src_record');
		$value = $request->get('value');
		$type = false;
		if (!empty($template)) {
			$recordModel = \App\Modules\Settings\TreesManager\Models\Record::getInstanceById($template);
		} else {
			\vtlib\Functions::throwNewException(\App\Runtime\Vtiger_Language_Handler::translate('ERR_TREE_NOT_FOUND', $moduleName));
		}
		if (!$recordModel)
			\vtlib\Functions::throwNewException(\App\Runtime\Vtiger_Language_Handler::translate('ERR_TREE_NOT_FOUND', $moduleName));
		if ($request->get('multiple')) {
			$type = 'category';
		}
		$tree = $recordModel->getTree($type, $value);
		$viewer->assign('TREE', \App\Json::encode($tree));
		$viewer->assign('SRC_RECORD', $srcRecord);
		$viewer->assign('SRC_FIELD', $srcField);
		$viewer->assign('TEMPLATE', $template);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('IS_MULTIPLE', $request->get('multiple'));
		$viewer->assign('TRIGGER_EVENT_NAME', $request->get('triggerEventName'));
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->view('TreePopup.tpl', $moduleName);
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $this->getModule($request);
		$viewer->assign('FOOTER_SCRIPTS', $this->getFooterScripts($request));
		$viewer->view('PopupFooter.tpl', $moduleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Vtiger\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = array('~libraries/jquery/jstree/jstree.js');
		if ($request->get('multiple')) {
			$jsFileNames[] = '~libraries/jquery/jstree/jstree.category.js';
			$jsFileNames[] = '~libraries/jquery/jstree/jstree.checkbox.js';
		}
		$jsFileNames = array_merge($jsFileNames, array(
			'libraries.jquery.jquery_windowmsg',
			'~libraries/jquery/clockpicker/jquery-clockpicker.js',
			'modules.Vtiger.resources.TreePopup',
			"modules.$moduleName.resources.TreePopup",
		));

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();
		$cssFileNames = array(
			'~libraries/jquery/jstree/themes/proton/style.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}

	protected function showBodyHeader()
	{
		return false;
	}
}
