<?php

/**
 * @package YetiForce.views
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class AJAXChat_Index_View extends Vtiger_Basic_View
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		
	}

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$shortURL = str_replace('index.php', '', AppConfig::main('site_URL'));
		$viewer = $this->getViewer($request);
		$viewer->assign('URLCSS', $shortURL . Yeti_Layout::getLayoutFile('modules/AJAXChat/Chat.css'));
		$viewer->assign('URL', $shortURL . "libraries/AJAXChat/index.php");
		$viewer->view('Index.tpl', 'AJAXChat');
	}
}
