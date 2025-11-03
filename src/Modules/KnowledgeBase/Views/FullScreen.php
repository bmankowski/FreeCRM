<?php

namespace App\Modules\KnowledgeBase\Views;

/**
 * Popup view for KnowledgeBase module
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Krzysztof Gastołek <krzysztof.gastolek@wars.pl>
 */

class FullScreen  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$previewView = new \App\Modules\KnowledgeBase\Views\PreviewContent();
		$previewView->process($request, false);
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($request->get('record'));
		$type = str_replace('PLL_', '', $recordModel->get('knowledgebase_view'));
		$template = ucfirst(strtolower($type)) . 'View.tpl';
		$viewer->assign('IS_POPUP', true);
		$viewer->view($template, $moduleName);
	}
}
