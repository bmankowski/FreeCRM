<?php

namespace App\Modules\Settings\Vtiger\Views;



class Policy extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->view('Policy.tpl', $qualifiedModuleName);
	}
}
