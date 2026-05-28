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
namespace App\Modules\Users\Views;


class Login extends \App\Base\Controllers\BaseViewController
{

	public function loginRequired()
	{
		return false;
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$selectedModule = $request->getModule();

		// Assignments moved from preProcess
		$companyDetails = \App\Core\Company::getInstanceById();
		$companyLogo = $companyDetails->getLogo();
		$viewer->assign('MODULE', $selectedModule);
		$viewer->assign('MODULE_NAME', $selectedModule);
		$viewer->assign('QUALIFIED_MODULE', $selectedModule);
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('COMPANY_LOGO', $companyLogo);
		// On login page, use backward compatibility method since no user is authenticated
		$viewer->assign('USER_MODEL', $request->getUser());

		$viewer->assign('CURRENT_VERSION', \App\Core\Version::get());
		$viewer->assign('LANGUAGE_SELECTION', \App\Core\AppConfig::main('langInLoginView'));
		$viewer->assign('LAYOUT_SELECTION', \App\Core\AppConfig::main('layoutInLoginView'));
		// Provide languages list to template instead of calling Vtiger_Language_Handler directly
		if (\App\Core\AppConfig::main('langInLoginView')) {
			$viewer->assign('AVAILABLE_LANGUAGES', \App\Runtime\Vtiger_Language_Handler::getAllLanguages());
			$viewer->assign('DEFAULT_LANGUAGE', \App\Core\AppConfig::main('default_language'));
		}
		$viewer->assign('ERROR', $request->get('error'));
		$viewer->assign('FPERROR', $request->get('fpError'));
		$viewer->assign('STATUS', $request->get('status'));
		$viewer->assign('STATUS_ERROR', $request->get('statusError'));
		$viewer->assign('STYLES', $this->getHeaderCss($request));
		$viewer->assign('HEADER_SCRIPTS', $this->getHeaderScripts($request));
		$viewer->assign('SYSTEM_MODE', \App\Core\AppConfig::main('systemMode'));
		$viewer->view('Login.tpl', 'Users');
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);

		$cssFileNames = [
			'skins.login',
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}
}
