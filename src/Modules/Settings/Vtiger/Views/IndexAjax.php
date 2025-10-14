<?php

namespace FreeCRM\Modules\Settings\Vtiger\Views;
use FreeCRM\Modules\Settings\Vtiger\Models\MenuItem;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class IndexAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getSettingsShortCutBlock');
		$this->exposeMethod('realignSettingsShortCutBlock');
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		return;
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();

		if ($mode) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function getSettingsShortCutBlock(\FreeCRM\Http\Vtiger_Request $request)
	{
		$fieldid = $request->get('fieldid');
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$pinnedSettingsShortcuts = \FreeCRM\Modules\Settings\Vtiger\Models\MenuItem::getPinnedItems();
		$viewer->assign('SETTINGS_SHORTCUT', $pinnedSettingsShortcuts[$fieldid]);
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->view('SettingsShortCut.tpl', $qualifiedModuleName);
	}

	public function realignSettingsShortCutBlock(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$pinnedSettingsShortcuts = \FreeCRM\Modules\Settings\Vtiger\Models\MenuItem::getPinnedItems();
		$viewer->assign('SETTINGS_SHORTCUT', $pinnedSettingsShortcuts);
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->view('ReAlignSettingsShortCut.tpl', $qualifiedModuleName);
	}
}
