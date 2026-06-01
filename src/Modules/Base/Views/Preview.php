<?php

namespace App\Modules\Base\Views;

use App\Http\Vtiger_Request;

class Preview extends \App\Modules\Base\Views\Index
{
	protected function showBodyHeader()
	{
		// Preview is rendered inside an iframe (ListPreview related list) and must not include the main app chrome.
		return false;
	}

	/**
	 * Checking permissions
	 *
	 * @param Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermittedToRecord
	 */
	public function checkPermission(Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		if (!is_numeric($recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		if (!\App\Modules\Users\Models\Privileges::isPermitted($request->getModule(), 'DetailView', (int) $recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function process(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');

		// Make widgets/templates behave exactly like DetailView context
		$request->set('view', 'Detail');
		$request->set('isReadOnly', 'true');

		$handlerClass = \App\Core\Loader::getComponentClassName('View', 'Detail', $moduleName);
		$detailView = new $handlerClass();

		// Match the Detail view "Summary" tab: widget grid (+ embedded MODULE_SUMMARY in Summary widget)
		// when the module supports it; otherwise the compact ModuleSummaryBlock only.
		$detailModel = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		$detailModel->getWidgets(['MODULE' => $moduleName, 'RECORD' => $recordId]);
		if (
			$detailModel->getModule()->isSummaryViewSupported()
			&& !empty($detailModel->widgetsList)
			&& method_exists($detailView, 'showModuleBasicView')
		) {
			$summaryHtml = $detailView->showModuleBasicView($request);
		} elseif (method_exists($detailView, 'showModuleSummaryView')) {
			$summaryHtml = $detailView->showModuleSummaryView($request);
		} else {
			$summaryHtml = '';
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('PREVIEW_HTML', $summaryHtml);
		$viewer->assign('PREVIEW_MODULE', $moduleName);
		$viewer->assign('PREVIEW_RECORD', $recordId);
		$viewer->view('IframePreview.tpl', 'Base');
	}
}

