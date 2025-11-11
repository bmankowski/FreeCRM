<?php

namespace App\Modules\Settings\Mail\Views;



/**
 * List View Class for Mail Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class ListView extends \App\Modules\Settings\Base\Views\ListView
{

	/**
	 * Function to get the page title
	 * @param \App\Http\Vtiger_Request $request
	 * @return string
	 */
	public function getPageTitle(\App\Http\Vtiger_Request $request)
	{
		return 'LBL_MAIL_QUEUE_PAGE_TITLE';
	}
	
	public function process(\App\Http\Vtiger_Request $request)
	{
		parent::process($request);
		
		// Prepare Mail-specific data for ListViewContent template
		$viewer = $this->getViewer($request);
		$this->prepareMailListViewData($viewer);
	}
	
	/**
	 * Prepare data for Mail ListViewContent template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareMailListViewData($viewer)
	{
		$viewer->assign('AUTO_REFRESH_LIST_ON_CHANGE', \App\AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE'));
		$viewer->assign('SMTP_NAMES', \App\Modules\Settings\MailSmtp\Models\Module::getSmtpNames());
		$viewer->assign('MAILER_STATUSES', \App\Mailer::$statuses);
	}
}
