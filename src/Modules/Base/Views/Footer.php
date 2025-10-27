<?php
/* +***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* *********************************************************************************** */

namespace App\Modules\Base\Views;

/**
 * Footer View Class
 * Responsible for rendering the application footer with branding, social links, and scripts
 */
abstract class Footer extends Header
{

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Post process - renders the footer
	 * Moved from BaseViewController to provide proper separation of concerns
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$currentUser = $request->getUser();
		
		// Assign footer-specific variables
		$viewer->assign('ACTIVITY_REMINDER', $currentUser->getCurrentUserActivityReminderInSeconds());
		$viewer->assign('COMPANY_LOGO', \App\Company::getInstanceById()->getLogo());
		$viewer->assign('FOOTER_SCRIPTS', $this->getFooterScripts($request));
		
		// Render the footer template
		$viewer->view('Footer.tpl');
	}
}
