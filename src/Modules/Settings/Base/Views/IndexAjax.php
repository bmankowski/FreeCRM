<?php

namespace App\Modules\Settings\Base\Views;
use App\Modules\Settings\Base\Models\MenuItem;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class IndexAjax extends \App\Modules\Settings\Base\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getSettingsShortCutBlock');
		$this->exposeMethod('realignSettingsShortCutBlock');
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		return;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();

		if ($mode) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function getSettingsShortCutBlock(\App\Http\Vtiger_Request $request)
	{
		$fieldid = $request->get('fieldid');
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$pinnedSettingsShortcuts = \App\Modules\Settings\Base\Models\MenuItem::getPinnedItems();
		$viewer->assign('SETTINGS_SHORTCUT', $pinnedSettingsShortcuts[$fieldid]);
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->view('SettingsShortCut.tpl', $qualifiedModuleName);
	}

	public function realignSettingsShortCutBlock(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$pinnedSettingsShortcuts = \App\Modules\Settings\Base\Models\MenuItem::getPinnedItems();
		$viewer->assign('SETTINGS_SHORTCUT', $pinnedSettingsShortcuts);
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->view('ReAlignSettingsShortCut.tpl', $qualifiedModuleName);
	}
}
