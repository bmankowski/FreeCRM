<?php

namespace App\Modules\Vtiger\Views;

/**
 * Quick detail modal view class
 * @package YetiForce.Modal
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;

class QuickDetailModal extends \Vtiger_Index_View
{

	/**
	 * Checking permissions
	 * @param Vtiger_Request $request
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
		return 'modalRightSiteBar';
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

		$widgets = [];
		foreach ($detailModel->widgets as $dw) {
			foreach ($dw as $widget) {
				if (!empty($widget['url'])) {
					parse_str($widget['url'], $output);
					$method = $output['mode'];
					$widgetRequest = new Vtiger_Request($output);
					$widgetRequest->set('isReadOnly', 'true');
					if ($detailView->isMethodExposed($method)) {
						$label = '';
						if (!empty($widget['label'])) {
							$label = \App\Runtime\Vtiger_Language_Handler::translate($widget['label'], $moduleName);
						} elseif ($widget['type'] === 'RelatedModule') {
							$relatedModule = \App\Module::getModuleName($widget['data']['relatedmodule']);
							$label = \App\Runtime\Vtiger_Language_Handler::translate($relatedModule, $relatedModule);
						}
						$widgets[] = ['title' => $label, 'content' => $detailView->$method($widgetRequest)];
					}
				} elseif ($widget['type'] === 'Summary') {
					$request->set('isReadOnly', 'true');
					$widgets[] = [
						'content' => $detailView->showModuleSummaryView($request)
					];
				}
			}
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('WIDGETS', $widgets);
		$viewer->view('QuickDetailModal.tpl', $moduleName);
		$this->postProcess($request);
	}
}
