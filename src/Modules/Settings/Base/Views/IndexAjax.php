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
		
		// Prepare SettingsShortCut-specific data for SettingsShortCut template
		$this->prepareSettingsShortCutData($viewer, $pinnedSettingsShortcuts[$fieldid]);
		
		$viewer->view('SettingsShortCut.tpl', $qualifiedModuleName);
	}

	public function realignSettingsShortCutBlock(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$pinnedSettingsShortcuts = \App\Modules\Settings\Base\Models\MenuItem::getPinnedItems();
		$viewer->assign('SETTINGS_SHORTCUT', $pinnedSettingsShortcuts);
		$viewer->assign('MODULE', $qualifiedModuleName);
		
		// Prepare ReAlignSettingsShortCut-specific data for ReAlignSettingsShortCut template
		$this->prepareReAlignSettingsShortCutData($viewer, $pinnedSettingsShortcuts);
		
		$viewer->view('ReAlignSettingsShortCut.tpl', $qualifiedModuleName);
	}
	
	/**
	 * Prepare data for SettingsShortCut template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareSettingsShortCutData($viewer, $shortcut)
	{
		$linkto = $shortcut->get('linkto');
		$viewer->assign('SHORTCUT_MODULE_NAME', \App\Modules\Base\Models\Menu::getModuleNameFromUrl($linkto));
	}
	
	/**
	 * Prepare data for ReAlignSettingsShortCut template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareReAlignSettingsShortCutData($viewer, $shortcuts)
	{
		$shortcutModuleNames = [];
		foreach ($shortcuts as $shortcut) {
			$shortcutModuleNames[$shortcut->getId()] = \App\Modules\Base\Models\Menu::getModuleNameFromUrl($shortcut->getUrl());
		}
		$viewer->assign('SHORTCUT_MODULE_NAMES', $shortcutModuleNames);
	}
}
