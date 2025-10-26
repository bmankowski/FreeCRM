<?php

namespace App\Modules\Settings\Users\Views;

/**
 * Settings Users ListView Class
 * Delegates to the main Users ListView
 */
class ListView extends \App\Modules\Users\Views\ListView
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		// Use the Settings header flow but with Users data
		\App\Modules\Settings\Base\Views\Index::preProcess($request, false);
		
		$viewer = $this->getViewer($request);
		$this->initializeListViewContents($request, $viewer);
		$viewer->view('ListViewHeader.tpl', 'Users');
	}
}

