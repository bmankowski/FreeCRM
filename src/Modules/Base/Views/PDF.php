<?php

namespace App\Modules\Base\Views;

/**
 * Export PDF Modal View Class
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class PDF  extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'ExportPdf')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->preProcess($request);
		$moduleName = $request->getModule();
		$allRecords = [];
		$recordId = $request->get('record');
		$view = $request->get('fromview');
		$allRecords = \App\Modules\Base\Actions\Mass::getRecordsListFromRequest($request);

		$handlerClass = \App\Core\Loader::getComponentClassName('Model', 'PDF', $moduleName);
		$pdfModel = new $handlerClass();

		$viewer = $this->getViewer($request);
		if ($view === 'Detail') {
			$pdfTemplates = $pdfModel->getActiveTemplatesForRecord($recordId, $view, $moduleName);
			$viewer->assign('TEMPLATES', $pdfTemplates);
			if (\count($pdfTemplates) === 1) {
				$viewer->assign('AUTO_GENERATE_SINGLE_PDF_TEMPLATE', true);
			}
		} elseif ($view === 'List' || $view === 'ListView') {
			$viewer->assign('TEMPLATES', $pdfModel->getActiveTemplatesForModule($moduleName, $view));
		}
		$postVars = [
			'record' => $recordId,
			'fromview' => $view
		];
		$viewer->assign('ALL_RECORDS', $allRecords);
		$viewer->assign('EXPORT_VARS', $postVars);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('SCRIPTS', $this->checkAndConvertJsScripts(['modules.Base.resources.PDF']));
		$viewer->view('ExportPDF.tpl', $moduleName);
		$this->postProcess($request);
	}
}
