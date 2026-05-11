<?php

namespace App\Modules\Settings\PDF\Views;



/**
 * List View Class for PDF Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ListView extends \App\Modules\Settings\Base\Views\ListView
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', \App\Modules\Settings\PDF\Models\Module::getSupportedModules());
		
		// Prepare PDF ListViewHeader-specific data for ListViewHeader template
		$this->preparePDFListViewHeaderData($viewer);
		
		parent::preProcess($request, $display);
	}
	
	/**
	 * Prepare data for PDF ListViewHeader template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function preparePDFListViewHeaderData($viewer)
	{
		$viewer->assign('MPDF_LIBRARY_CHECK', \App\Modules\Settings\ModuleManager\Models\Library::checkLibrary('mPDF'));
		$viewer->assign('CREATE_RECORD_URL', \App\Modules\Settings\PDF\Models\Module::getCreateRecordUrl());
		$viewer->assign('IMPORT_VIEW_URL', \App\Modules\Settings\PDF\Models\Module::getImportViewUrl());
	}
}
