<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use FreeCRM\Runtime\Vtiger_View_Controller;
use FreeCRM\Http\Vtiger_Request;
use FreeCRM\Runtime\Vtiger_Theme;
use FreeCRM\AppConfig;

abstract class Vtiger_Header_View extends Vtiger_View_Controller
{

	public function __construct()
	{
		parent::__construct();
	}
	//Note : To get the right hook for immediate parent in PHP,
	// specially in case of deep hierarchy
	/* function preProcessParentTplName(\FreeCRM\Http\Vtiger_Request $request) {
	  return parent::preProcessTplName($request);
	  } */

	/**
	 * Function to determine file existence in relocated module folder (under vtiger6)
	 * @param String $fileuri
	 * @return Boolean
	 *
	 * Utility function to manage the backward compatible file load
	 * which are registered for 5.x modules (and now provided for 6.x as well).
	 */
	protected function checkFileUriInRelocatedMouldesFolder($fileuri)
	{
		if (strpos($fileuri, '?') !== false) {
			list ($filename, $query) = explode('?', $fileuri);
		} else {
			$filename = $fileuri;
		}
		// prefix the base lookup folder (relocated file).
		if (strpos($filename, 'modules') === 0) {
			$filename = $filename;
		}

		return file_exists($filename);
	}

	/**
	 * Function to get the list of Header Links
	 * @return <Array> - List of \FreeCRM\Modules\Vtiger\Models\Link instances
	 */
	public function getMenuHeaderLinks(\FreeCRM\Http\Vtiger_Request $request)
	{
		$userModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$headerLinks = [];
		if (\FreeCRM\Modules\Users\Models\Module::getSwitchUsers()) {
			$headerLinks[] = [
				'linktype' => 'HEADERLINK',
				'linklabel' => 'SwitchUsers',
				'linkurl' => '',
				'glyphicon' => 'glyphicon glyphicon-transfer',
				'nocaret' => true,
				'linkdata' => ['url' => $userModel->getSwitchUsersUrl()],
				'linkclass' => 'showModal',
			];
		}
		if (\FreeCRM\AppConfig::security('SHOW_MY_PREFERENCES')) {
			$headerLinks[] = [
				'linktype' => 'HEADERLINK',
				'linklabel' => 'LBL_MY_PREFERENCES',
				'linkurl' => $userModel->getPreferenceDetailViewUrl(),
				'glyphicon' => 'glyphicon glyphicon-tasks',
			];
		}
		if ($userModel->isAdminUser()) {
			if ($request->get('parent') != 'Settings') {
				$headerLinks[] = [
					'linktype' => 'HEADERLINK',
					'linklabel' => 'LBL_SYSTEM_SETTINGS',
					'linkurl' => 'index.php?module=Vtiger&parent=Settings&view=Index',
					'glyphicon' => 'glyphicon glyphicon-cog',
				];
			} else {
				$headerLinks[] = [
					'linktype' => 'HEADERLINK',
					'linklabel' => 'LBL_USER_PANEL',
					'linkurl' => 'index.php',
					'glyphicon' => 'glyphicon glyphicon-user',
				];
			}
		}
		$headerLinks[] = [
			'linktype' => 'HEADERLINK',
			'linklabel' => 'LBL_SIGN_OUT',
			'linkurl' => 'index.php?module=Users&parent=Settings&action=Logout',
			'glyphicon' => 'glyphicon glyphicon-off',
			'linkclass' => 'btn-danger'
		];
		$headerLinkInstances = [];
		foreach ($headerLinks as $headerLink) {
			$headerLinkInstance = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($headerLink);
			if (isset($headerLink['childlinks'])) {
				foreach ($headerLink['childlinks'] as $childLink) {
					$headerLinkInstance->addChildLink(\FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($childLink));
				}
			}
			$headerLinkInstances[] = $headerLinkInstance;
		}
		$headerLinks = \FreeCRM\Modules\Vtiger\Models\Link::getAllByType(\vtlib\Link::IGNORE_MODULE, ['HEADERLINK']);
		foreach ($headerLinks as $headerType => $headerLinks) {
			foreach ($headerLinks as $headerLink) {
				$headerLinkInstances[] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromLinkObject($headerLink);
			}
		}
		return $headerLinkInstances;
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of \FreeCRM\Modules\Vtiger\Models\JsScript instances
	 */
	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$headerScripts = \FreeCRM\Modules\Vtiger\Models\Link::getAllByType(\vtlib\Link::IGNORE_MODULE, array('HEADERSCRIPT'));
		foreach ($headerScripts as $headerType => $headerScripts) {
			foreach ($headerScripts as $headerScript) {
				if ($this->checkFileUriInRelocatedMouldesFolder($headerScript->linkurl)) {
					$headerScriptInstances[] = \FreeCRM\Runtime\Vtiger_JsScript_Model::getInstanceFromLinkObject($headerScript);
				}
			}
		}
		return $headerScriptInstances;
	}

	/**
	 * Function to get the list of Css models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of \FreeCRM\Modules\Vtiger\Models\CssScript instances
	 */
	public function getHeaderCss(\FreeCRM\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$baseStyleCssPath = Vtiger_Theme::getBaseStylePath();
		$baseStyleCssPath = $this->checkAndConvertCssStyles(['~' . $baseStyleCssPath]);
		$headerCssInstances = array_merge($headerCssInstances, $baseStyleCssPath);

		$headerCss = \FreeCRM\Modules\Vtiger\Models\Link::getAllByType(\vtlib\Link::IGNORE_MODULE, ['HEADERCSS']);
		$selectedThemeCssPath = Vtiger_Theme::getThemeStyle();
		$cssScriptModel = new \FreeCRM\Runtime\Vtiger_CssScript_Model();
		$headerCssInstances[] = $cssScriptModel->set('href', $selectedThemeCssPath);

		foreach ($headerCss as $headerType => $cssLinks) {
			foreach ($cssLinks as $cssLink) {
				if ($this->checkFileUriInRelocatedMouldesFolder($cssLink->linkurl)) {
					$headerCssInstances[] = \FreeCRM\Runtime\Vtiger_CssScript_Model::getInstanceFromLinkObject($cssLink);
				}
			}
		}
		return $headerCssInstances;
	}
}
