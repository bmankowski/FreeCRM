<?php

namespace App\Modules\AJAXChat\Views;

/**
 * @package YetiForce.views
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class Index  extends \App\Modules\Vtiger\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$shortURL = str_replace('index.php', '', \App\AppConfig::main('site_URL'));
		$viewer = $this->getViewer($request);
		$viewer->assign('URLCSS', $shortURL . \App\Runtime\Yeti_Layout::getLayoutFile('src/Modules/AJAXChat/Chat.css'));
		$viewer->assign('URL', $shortURL . "libraries/AJAXChat/index.php");
		$viewer->view('Index.tpl', 'AJAXChat');
	}
}
