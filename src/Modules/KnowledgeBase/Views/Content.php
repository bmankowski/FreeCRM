<?php

namespace App\Modules\KnowledgeBase\Views;

/**
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class Content  extends \App\Modules\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$previewContent = new \App\Modules\KnowledgeBase\Views\PreviewContent();
			$previewContent->process($request);
		} else {
			$moduleName = $request->getModule();
			$pagingModel = new \App\Modules\Vtiger\Models\Paging();
			$pagingModel->set('limit', 'no_limit');
			$listViewModel = \App\Modules\Vtiger\Models\ListView::getInstance($moduleName);
			$listEntries = $listViewModel->getListViewEntries($pagingModel);
			$headers = $listViewModel->getListViewHeaders();

			$viewer = $this->getViewer($request);
			$viewer->assign('VIEW', $request->get('view'));
			$viewer->assign('ENTRIES', $listEntries);
			$viewer->assign('HEADERS', $headers);
			$viewer->assign('MODULE_NAME', $moduleName);
			$viewer->view('ContentsDefault.tpl', $moduleName);
		}
	}
}
