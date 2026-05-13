<?php

namespace App\Modules\Settings\Template\Views;



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
		$viewer->assign('SUPPORTED_MODULE_MODELS', \App\Modules\Settings\Template\Models\Module::getSupportedModules());
		
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
		$viewer->assign('CREATE_RECORD_URL', \App\Modules\Settings\Template\Models\Module::getCreateRecordUrl());
		$viewer->assign('IMPORT_VIEW_URL', \App\Modules\Settings\Template\Models\Module::getImportViewUrl());
	}
}
