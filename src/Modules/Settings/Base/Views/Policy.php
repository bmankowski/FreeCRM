<?php

namespace App\Modules\Settings\Base\Views;



class Policy extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->view('Policy.tpl', $qualifiedModuleName);
	}
}
