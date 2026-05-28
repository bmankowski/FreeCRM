<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\DocumentTemplates\Views;

class ListView extends \App\Modules\Base\Views\ListView
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		\App\Modules\DocumentTemplates\Models\Module::checkRequestPermission($request, 'ListView');
	}

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$moduleName = $request->getModule();
		if (!isset($this->viewName)) {
			$this->viewName = \App\View\CustomView::getInstance($moduleName)->getViewId();
		}
		if (!$this->listViewModel instanceof \App\Modules\DocumentTemplates\Models\ListView) {
			$this->listViewModel = \App\Modules\DocumentTemplates\Models\ListView::getInstance(
				$moduleName,
				$this->viewName
			);
		}
		$sourceModule = $request->get('sourceModule');
		if (!empty($sourceModule)) {
			$this->listViewModel->set('sourceModule', $sourceModule);
		}
		parent::initializeListViewContents($request, $viewer);
	}
}
