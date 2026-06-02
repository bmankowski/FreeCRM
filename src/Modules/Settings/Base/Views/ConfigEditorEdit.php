<?php

namespace App\Modules\Settings\Base\Views;
use App\Modules\Settings\Base\Models\ConfigModule;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class ConfigEditorEdit extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\Base\Models\ConfigModule::getInstance();

		// Prepare upload size limits for template
		$maxUploadBytes = min(
			\App\Modules\Base\Helpers\Util::parseHumanReadableToBytes(ini_get('upload_max_filesize')),
			\App\Modules\Base\Helpers\Util::parseHumanReadableToBytes(ini_get('post_max_size'))
		);
		$maxUploadSizeHuman = \App\Modules\Base\Helpers\Util::formatBytesToHumanReadable($maxUploadBytes);

		$viewer = $this->getViewer($request);
		$viewer->assign('MODEL', $moduleModel);
		$viewer->assign('MAX_UPLOAD_SIZE_BYTES', $maxUploadBytes);
		$viewer->assign('MAX_UPLOAD_SIZE_HUMAN', $maxUploadSizeHuman);
		
		// Prepare field validation data with JSON encoding
		$fieldValidation = [
			'HELPDESK_SUPPORT_EMAIL_REPLY' => ['name'=>'Email'],
			'upload_maxsize' => ['name' => 'number'],
			'history_max_viewed' => ['name' => 'NumberRange5'],
			'popupType' =>['name' => 'NumberRange2'],
			'title_max_length' => ['name' => 'NumberRange100'],
			'MINIMUM_CRON_FREQUENCY' => ['name' => 'NumberRange100'],
			'href_max_length' => ['name' => 'NumberRange100'],
			'listview_max_textlength' => ['name' => 'NumberRange100'],
			'list_max_entries_per_page' => ['name' => 'NumberRange100']
		];
		$fieldValidationJson = [];
		foreach ($fieldValidation as $fieldName => $validation) {
			$fieldValidationJson[$fieldName] = \App\Utils\Json::encode([$validation]);
		}
		$viewer->assign('FIELD_VALIDATION_JSON', $fieldValidationJson);
		
		$viewer->view('ConfigEditorEdit.tpl', $qualifiedName);
	}

	public function getPageTitle(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		return \App\Runtime\Vtiger_Language_Handler::translate('LBL_CONFIG_EDITOR', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.resources.ConfigEditor"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
