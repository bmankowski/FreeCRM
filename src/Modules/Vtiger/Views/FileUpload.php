<?php

namespace App\Modules\Vtiger\Views;

/**
 * FileUpload View Class
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

/**
 * FileUpload view class
 */

use App\Http\Vtiger_Request;
class FileUpload extends \Vtiger_Index_View
{

	/**
	 * Checking permission
	 * @param Vtiger_Request $request
	 * @throws \Exception\NoPermittedToRecord
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$fieldName = $request->get('inputName');
		if (!empty($record)) {
			$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
			if (!$recordModel->isEditable() || !\App\Field::getFieldPermission($moduleName, $fieldName, false)) {
				throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
			}
		} else {
			if (!\App\Field::getFieldPermission($moduleName, $fieldName, false) || !\App\Privilege::isPermitted($moduleName, 'CreateView')) {
				throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
			}
		}
	}

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$this->preProcess($request);
		$viewer->assign('INPUT_NAME', $request->get('inputName'));
		$viewer->assign('FILE_TYPE', $request->get('fileType'));
		$viewer->assign('RECORD', $request->get('record'));
		$viewer->view('FileUpload.tpl', $moduleName);
		$this->postProcess($request);
	}

	/**
	 * Get scripts for modal window
	 * @param Vtiger_Request $request
	 * @return \App\Modules\Vtiger\Models\JsScript[]
	 */
	public function getModalScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getModalScripts($request);
		$scripts = [
			'libraries.jquery.multiplefileupload.jquery_MultiFile'
		];
		$scriptInstances = $this->checkAndConvertJsScripts($scripts);
		return array_merge($scriptInstances, $headerScriptInstances);
	}
}
