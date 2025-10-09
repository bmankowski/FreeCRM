<?php

namespace FreeCRM\Modules\KnowledgeBase\Views;

/**
 * Popup view for KnowledgeBase module
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Krzysztof Gastołek <krzysztof.gastolek@wars.pl>
 */

use FreeCRM\Http\Vtiger_Request;
class FullScreen extends View
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$previewView = new KnowledgeBase_PreviewContent_View();
		$previewView->process($request, false);
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$recordModel = Vtiger_Record_Model::getInstanceById($request->get('record'));
		$type = str_replace('PLL_', '', $recordModel->get('knowledgebase_view'));
		$template = ucfirst(strtolower($type)) . 'View.tpl';
		$viewer->assign('IS_POPUP', true);
		$viewer->view($template, $moduleName);
	}
}
