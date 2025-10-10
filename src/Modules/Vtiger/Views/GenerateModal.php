<?php

namespace FreeCRM\Modules\Vtiger\Views;

/**
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class GenerateModal extends \Vtiger_Index_View
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		echo '<div class="generateMappingModal modal fade"><div class="modal-dialog"><div class="modal-content">';
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		
		\App\Log::trace('Entering ' . __METHOD__ . '() method ...');

		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$view = $request->get('fromview');
		$viewer = $this->getViewer($request);
		$handlerClass = \FreeCRM\Loader::getComponentClassName('Model', 'MappedFields', $moduleName);
		$mfModel = new $handlerClass();
		if ($view == 'List') {
			$allRecords = Vtiger_Mass_Action::getRecordsListFromRequest($request);
			$templates = $mfModel->getActiveTemplatesForModule($moduleName, $view);
			$viewer->assign('ALL_RECORDS', $allRecords);
		} else {
			$templates = $mfModel->getActiveTemplatesForRecord($recordId, $view, $moduleName);
			$viewer->assign('RECORD', $recordId);
		}

		$viewer->assign('TEMPLATES', $templates);
		$viewer->assign('VIEW', $view);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('BASE_MODULE_NAME', 'Vtiger');
		$this->preProcess($request);
		$viewer->view('GenerateModal.tpl', $moduleName);
		$this->postProcess($request);
		\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
	}
}
