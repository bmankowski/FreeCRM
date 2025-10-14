<?php

namespace FreeCRM\Modules\Settings\Vtiger\Views;



class Policy extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->view('Policy.tpl', $qualifiedModuleName);
	}
}
