<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */



namespace FreeCRM\Runtime;

use FreeCRM\Http\Vtiger_Request;
use FreeCRM\Runtime\FreeCRM_Viewer;
use FreeCRM\LanguageTranslator;
use FreeCRM\Runtime\Vtiger_Theme;
use FreeCRM\Runtime\Yeti_Layout;
use FreeCRM\Runtime\Vtiger_Language_Handler;
use FreeCRM\Vtiger_Loader;
use FreeCRM\Runtime\Vtiger_CssScript_Model;
abstract class Vtiger_View_Controller extends Vtiger_Action_Controller
{

   public $pageTitle;
   protected $viewer;

   public function __construct()
   {
	   parent::__construct();
   }

   public function getViewer(Vtiger_Request $vtigerRequest)
   {
	   if ($this->viewer === null) {
		   $viewer = FreeCRM_Viewer::getInstance();
		   $viewer->assign('APPTITLE', \FreeCRM\Runtime\Vtiger_Language_Handler::translate('APPTITLE'));
		   $viewer->assign('YETIFORCE_VERSION', \App\Version::get());
		   $viewer->assign('MODULE_NAME', $vtigerRequest->getModule());
		   if ($vtigerRequest->isAjax()) {
			   $viewer->assign('USER_MODEL', \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel());
			   if ($vtigerRequest->get('parent') === 'Settings') {
				   $viewer->assign('QUALIFIED_MODULE', $vtigerRequest->getModule(false));
			   }
		   }

		   $this->viewer = $viewer;
	   }

	   return $this->viewer;
   }

   public function getPageTitle(Vtiger_Request $vtigerRequest)
   {
	   $moduleName = $vtigerRequest->getModule(false);
	   $moduleNameArray = explode(':', $moduleName);
	   $moduleLabel = end($moduleNameArray) === 'Vtiger' ? 'YetiForce' : end($moduleNameArray);
	   $title = \FreeCRM\Runtime\Vtiger_Language_Handler::translate($moduleLabel, $moduleName);
	   $pageTitle = $this->getBreadcrumbTitle($vtigerRequest);
	   if ($pageTitle) {
		   $title .= ' - ' . $pageTitle;
	   }

	   return $title;
   }

   public function getBreadcrumbTitle(Vtiger_Request $vtigerRequest)
   {
	   if (!empty($this->pageTitle)) {
		   return $this->pageTitle;
	   }

	   return 0;
   }

   public function preProcess(Vtiger_Request $vtigerRequest, $display = true)
   {
	   $moduleName = $vtigerRequest->getModule();
	   $viewer = $this->getViewer($vtigerRequest);
	   $viewer->assign('PAGETITLE', $this->getPageTitle($vtigerRequest));
	   $viewer->assign('BREADCRUMB_TITLE', $this->getBreadcrumbTitle($vtigerRequest));
	   $viewer->assign('HEADER_SCRIPTS', $this->getHeaderScripts($vtigerRequest));
	   $viewer->assign('STYLES', $this->getHeaderCss($vtigerRequest));
	   $viewer->assign('SKIN_PATH', Vtiger_Theme::getCurrentUserThemePath());
	   $viewer->assign('LAYOUT_PATH', 'layouts/' . Yeti_Layout::getActiveLayout());
	   $viewer->assign('LANGUAGE_STRINGS', $this->getJSLanguageStrings($vtigerRequest));
	   $viewer->assign('HTMLLANG', Vtiger_Language_Handler::getShortLanguageName());
	   $viewer->assign('LANGUAGE', Vtiger_Language_Handler::getLanguage());
	   $viewer->assign('SHOW_BODY_HEADER', $this->showBodyHeader());
	   $viewer->assign('USER_MODEL', \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel());
	   $viewer->assign('MODULE', $moduleName);
	   $viewer->assign('VIEW', $vtigerRequest->get('view'));
	   $viewer->assign('MODULE_NAME', $moduleName);
	   $viewer->assign('PARENT_MODULE', $vtigerRequest->get('parent'));
	   
	   // Build array of all module active statuses for templates
	   $activeModules = [];
	   $allModules = \vtlib\Functions::getAllModules(false, true);  // Get ALL modules, not just entity types
	   foreach ($allModules as $module) {
		   $activeModules[$module['name']] = \App\Module::isModuleActive($module['name']);
	   }
	   $viewer->assign('ACTIVE_MODULES', $activeModules);
	   
	   if ($display) {
		   $this->preProcessDisplay($vtigerRequest);
	   }
   }

   protected function preProcessTplName(Vtiger_Request $vtigerRequest)
   {
	   return 'Header.tpl';
   }

   protected function showBodyHeader()
   {
	   return true;
   }

   protected function preProcessDisplay(Vtiger_Request $vtigerRequest)
   {
	   $viewer = $this->getViewer($vtigerRequest);
	   $viewer->view($this->preProcessTplName($vtigerRequest), $vtigerRequest->getModule());
   }

   /**
	* Post process
	*/
   public function postProcess(Vtiger_Request $vtigerRequest)
   {
	   $viewer = $this->getViewer($vtigerRequest);
	   $currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
	   $viewer->assign('ACTIVITY_REMINDER', $currentUser->getCurrentUserActivityReminderInSeconds());
	   $viewer->assign('COMPANY_LOGO', \App\Company::getInstanceById()->getLogo());
	   $viewer->assign('FOOTER_SCRIPTS', $this->getFooterScripts($vtigerRequest));
	   $viewer->view('Footer.tpl');
   }

   /**
	* Retrieves css styles that need to loaded in the page
	* @param Vtiger_Request $vtigerRequest - request model
	* @return <array> - array of Vtiger_CssScript_Model
	*/
   public function getHeaderCss(Vtiger_Request $vtigerRequest)
   {
	   $cssFileNames = [
		   '~libraries/bootstrap3/css/bootstrap.css',
		   '~libraries/font-awesome/css/font-awesome.css',
		   'skins.icons.userIcons',
		   'skins.icons.adminIcons',
		   'skins.icons.additionalIcons',
		   '~libraries/jquery/chosen/chosen.css',
		   '~libraries/jquery/chosen/chosen.bootstrap.css',
		   '~libraries/jquery/jquery-ui/jquery-ui.css',
		   '~libraries/jquery/selectize/css/selectize.bootstrap3.css',
		   '~libraries/jquery/select2/select2.css',
		   '~libraries/jquery/perfect-scrollbar/css/perfect-scrollbar.css',
		   '~libraries/jquery/select2/select2-bootstrap.css',
		   '~libraries/jquery/posabsolute-jQuery-Validation-Engine/css/validationEngine.jquery.css',
		   '~libraries/jquery/pnotify/pnotify.custom.css',
		   '~libraries/jquery/datepicker/css/datepicker.css',
		   '~libraries/footable/css/footable.core.css',
		   '~libraries/jquery/timepicker/jquery.timepicker.css',
		   '~libraries/jquery/clockpicker/bootstrap-clockpicker.css',
		   'libraries.resources.styles',
	   ];
	   return $this->checkAndConvertCssStyles($cssFileNames);
   }

   /**
	* Retrieves headers scripts that need to loaded in the page
	* @param Vtiger_Request $vtigerRequest - request model
	* @return <array> - array of Vtiger_JsScript_Model
	*/
   public function getHeaderScripts(Vtiger_Request $vtigerRequest)
   {
	   $headerScriptInstances = [
		   'libraries.jquery.jquery',
		   'libraries.jquery.jquery-migrate'
	   ];
	   return $this->checkAndConvertJsScripts($headerScriptInstances);
   }

   public function getFooterScripts(Vtiger_Request $vtigerRequest)
   {
	   $jsFileNames = [
		   '~libraries/jquery/jquery.blockUI.js',
		   '~libraries/jquery/chosen/chosen.jquery.js',
		   '~libraries/jquery/select2/select2.full.js',
		   '~libraries/jquery/jquery-ui/jquery-ui.js',
		   '~libraries/jquery/jquery.class.js',
		   '~libraries/jquery/defunkt-jquery-pjax/jquery.pjax.js',
		   '~libraries/jquery/jstorage.js',
		   '~libraries/jquery/autosize/jquery.autosize-min.js',
		   '~libraries/jquery/perfect-scrollbar/js/perfect-scrollbar.jquery.js',
		   '~libraries/jquery/rochal-jQuery-slimScroll/jquery.slimscroll.js',
		   '~libraries/jquery/pnotify/pnotify.custom.js',
		   '~libraries/jquery/jquery.hoverIntent.minified.js',
		   '~libraries/bootstrap3/js/bootstrap.js',
		   '~libraries/bootstrap3/js/bootstrap-switch.js',
		   '~libraries/bootstrap3/js/bootbox.js',
		   '~libraries/jquery/selectize/js/selectize.js',
		   '~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/jquery.validationEngine.js',
		   '~libraries/jquery/datepicker/js/datepicker.js',
		   '~libraries/jquery/dangrossman-bootstrap-daterangepicker/date.js',
		   '~libraries/jquery/jquery.ba-outside-events.js',
		   '~libraries/jquery/jquery.placeholder.js',
		   '~libraries/jquery/dompurify/purify.js',
		   '~libraries/footable/js/footable.js',
		   '~libraries/resources/jquery.additions.js',
		   'libraries.resources.app',
		   'libraries.resources.helper',
		   'libraries.resources.Connector',
		   'libraries.resources.ProgressIndicator',
	   ];

	   $languageHandlerShortName = Vtiger_Language_Handler::getShortLanguageName();
	   $fileName = sprintf('libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-%s.js', $languageHandlerShortName);
	   if (!file_exists($fileName)) {
		   $fileName = "~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-en.js";
	   } else {
		   $fileName = sprintf('~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-%s.js', $languageHandlerShortName);
	   }

	   $jsFileNames[] = $fileName;
	   return $this->checkAndConvertJsScripts($jsFileNames);
   }

   public function checkAndConvertJsScripts($jsFileNames)
   {
	   $fileExtension = 'js';
	   $jsScriptInstances = [];

	   foreach ($jsFileNames as $jFileName) {
		   $jsScript = new Vtiger_JsScript_Model();

		   // external javascript source file handling
		   if (strpos($jFileName, 'http://') === 0 || strpos($jFileName, 'https://') === 0) {
			   $jsScriptInstances[$jFileName] = $jsScript->set('src', $jFileName);
			   continue;
		   }

		   $completeFilePath = Vtiger_Loader::resolveNameToPath($jFileName, $fileExtension);
		   if (is_file($completeFilePath)) {
			   if (strpos($jFileName, '~') === 0) {
				   $filePath = ltrim(ltrim($jFileName, '~'), '/');
			   } else {
				   $filePath = str_replace('.', '/', $jFileName) . '.' . $fileExtension;
			   }

			   $minFilePath = str_replace('.js', '.min.js', $filePath);
			   if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(Vtiger_Loader::resolveNameToPath('~' . $minFilePath, $fileExtension))) {
				   $filePath = $minFilePath;
			   }

			   $jsScriptInstances[$jFileName] = $jsScript->set('src', $filePath);
			   continue;
		   }
		   $preLayoutPath = '';
		   if (strpos($jFileName, '~') === 0) {
				   $jsFile = ltrim(ltrim($jFileName, '~'), '/');
				   $preLayoutPath = '~';
			   } else {
				   $jsFile = $jFileName;
			   }
		   // Checking if file exists in selected layout
		   $layoutPath = 'layouts/' . Yeti_Layout::getActiveLayout();
		   $fallBackFilePath = Vtiger_Loader::resolveNameToPath($preLayoutPath . $layoutPath . '/' . $jsFile, $fileExtension);
		   if (is_file($fallBackFilePath)) {
				   $filePath = $jsFile;
			   if ($preLayoutPath === '' || $preLayoutPath === '0') {
				   $filePath = str_replace('.', '/', $filePath) . '.js';
			   }

				   $minFilePath = str_replace('.js', '.min.js', $filePath);
				   if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(Vtiger_Loader::resolveNameToPath('~' . $layoutPath . '/' . $minFilePath, $fileExtension))) {
					   $filePath = $minFilePath;
				   }

				   $jsScriptInstances[$jFileName] = $jsScript->set('src', $layoutPath . '/' . $filePath);
				   continue;
			   }
		   // Checking if file exists in default layout
		   $layoutPath = 'layouts/' . FreeCRM_Viewer::getDefaultLayoutName();
		   $fallBackFilePath = Vtiger_Loader::resolveNameToPath($preLayoutPath . $layoutPath . '/' . $jsFile, $fileExtension);
		   if (is_file($fallBackFilePath)) {
				   $filePath = $jsFile;
			   if ($preLayoutPath === '' || $preLayoutPath === '0') {
				   $filePath = str_replace('.', '/', $jsFile) . '.js';
			   }

				   $minFilePath = str_replace('.js', '.min.js', $filePath);
				   if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(Vtiger_Loader::resolveNameToPath('~' . $layoutPath . '/' . $minFilePath, $fileExtension))) {
					   $filePath = $minFilePath;
				   }

				   $jsScriptInstances[$jFileName] = $jsScript->set('src', $layoutPath . '/' . $filePath);
				   continue;
			   }
	   }

	   return $jsScriptInstances;
   }

   /**
	* Function returns the css files
	* @param <Array> $cssFileNames
	* @return <Array of Vtiger_CssScript_Model>
	*
	* First check if $cssFileName exists
	* if not, check under layout folder $cssFileName eg:layouts/basic/$cssFileName
	*/
   public function checkAndConvertCssStyles($cssFileNames, string $fileExtension = 'css')
   {
	   $cssStyleInstances = [];
	   foreach ($cssFileNames as $cssFileName) {
		   $cssScriptModel = new Vtiger_CssScript_Model();
		   if (strpos($cssFileName, 'http://') === 0 || strpos($cssFileName, 'https://') === 0) {
			   $cssStyleInstances[] = $cssScriptModel->set('href', $cssFileName);
			   continue;
		   }

		   $completeFilePath = Vtiger_Loader::resolveNameToPath($cssFileName, $fileExtension);
		   if (file_exists($completeFilePath)) {
			   if (strpos($cssFileName, '~') === 0) {
				   $filePath = ltrim(ltrim($cssFileName, '~'), '/');
			   } else {
				   $filePath = str_replace('.', '/', $cssFileName) . '.' . $fileExtension;
			   }

			   $minFilePath = str_replace('.css', '.min.css', $filePath);
			   if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(Vtiger_Loader::resolveNameToPath('~' . $minFilePath, $fileExtension))) {
				   $filePath = $minFilePath;
			   }

			   $cssStyleInstances[$cssFileName] = $cssScriptModel->set('href', $filePath);
			   continue;
		   }
		   $preLayoutPath = '';
		   if (strpos($cssFileName, '~') === 0) {
				   $cssFile = ltrim(ltrim($cssFileName, '~'), '/');
				   $preLayoutPath = '~';
			   } else {
				   $cssFile = $cssFileName;
			   }
		   // Checking if file exists in selected layout
		   $layoutPath = 'layouts/' . Yeti_Layout::getActiveLayout();
		   $fallBackFilePath = Vtiger_Loader::resolveNameToPath($preLayoutPath . $layoutPath . '/' . $cssFile, $fileExtension);
		   if (is_file($fallBackFilePath)) {
				   if ($preLayoutPath === '' || $preLayoutPath === '0') {
				   $filePath = str_replace('.', '/', $cssFile) . '.css';
			   }

			   $minFilePath = str_replace('.css', '.min.css', $filePath);
				   if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(Vtiger_Loader::resolveNameToPath('~' . $layoutPath . '/' . $minFilePath, $fileExtension))) {
					   $filePath = $minFilePath;
				   }

				   $cssStyleInstances[$cssFileName] = $cssScriptModel->set('href', $layoutPath . '/' . $filePath);
				   continue;
			   }
		   // Checking if file exists in default layout
		   $layoutPath = 'layouts/' . FreeCRM_Viewer::getDefaultLayoutName();
		   $fallBackFilePath = Vtiger_Loader::resolveNameToPath($preLayoutPath . $layoutPath . '/' . $cssFile, $fileExtension);
		   if (is_file($fallBackFilePath)) {
				   if ($preLayoutPath === '' || $preLayoutPath === '0') {
				   $filePath = str_replace('.', '/', $cssFile) . '.css';
			   }

			   $minFilePath = str_replace('.css', '.min.css', $filePath);
				   if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(Vtiger_Loader::resolveNameToPath('~' . $layoutPath . '/' . $minFilePath, $fileExtension))) {
					   $filePath = $minFilePath;
				   }

				   $cssStyleInstances[$cssFileName] = $cssScriptModel->set('href', $layoutPath . '/' . $filePath);
				   continue;
			   }
	   }

	   return $cssStyleInstances;
   }

   /**
	* Function returns the Client side language string
	*/
   public function getJSLanguageStrings(Vtiger_Request $vtigerRequest)
   {
	   $moduleName = $vtigerRequest->getModule(false);
	   if ($moduleName === 'Settings:Users') {
		   $moduleName = 'Users';
	   }

	   return Vtiger_Language_Handler::export($moduleName, 'jsLanguageStrings');
   }
}
