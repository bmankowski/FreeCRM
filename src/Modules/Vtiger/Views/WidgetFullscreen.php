<?php

namespace App\Modules\Vtiger\Views;

/**
 * Widget fullscreen modal view class
 * @package YetiForce.Modal
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

/**
 * Widget fullscreen modal view class
 */

use App\Http\Vtiger_Request;

class WidgetFullscreen  extends \App\Modules\Vtiger\Views\Index
{

	/**
	 * Checking permissions
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \Exception\AppException
	 * @throws \Exception\NoPermittedToRecord
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		if (!is_numeric($recordId)) {
			throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($request->getModule(), 'DetailView', $recordId);
		if (!$recordPermission) {
			throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return 'modal-blg';
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->preProcess($request);
		$moduleName = $request->getModule();
		$detailModel = \App\Modules\Vtiger\Models\DetailView::getInstance($moduleName, $request->get('record'));
		$recordModel = $detailModel->getRecord();
		$detailModel->getWidgets();
		$handlerClass = \App\Loader::getComponentClassName('View', 'Detail', $moduleName);
		$detailView = new $handlerClass();
		$mode = $request->getMode();
		$request->set('limit', 30);
		$request->set('isFullscreen', 'true');
		if ($detailView->isMethodExposed($mode)) {
			$content = $detailView->$mode($request);
		}
		$title = 'xx';
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('CONTENT', $content);
		$viewer->assign('TITLE', $title);
		$viewer->view('WidgetFullscreen.tpl', $moduleName);
		$this->postProcess($request);
	}
}
