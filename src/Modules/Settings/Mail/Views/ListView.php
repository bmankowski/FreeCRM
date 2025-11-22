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
	
	/**
	 * Prepare data for ListViewHeader templates
	 * Override to add Mail-specific data before template rendering
	 */
	protected function prepareListViewData(\App\Http\Vtiger_Request $request)
	{
		// Call parent to set up base data
		parent::prepareListViewData($request);
		
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
		// SMTP_NAMES and MAILER_STATUSES are Mail-specific and should always be initialized
		$viewer->assign('SMTP_NAMES', \App\Modules\Settings\MailSmtp\Models\Module::getSmtpNames());
		$viewer->assign('MAILER_STATUSES', \App\Email\Mailer::$statuses ?? []);
	}
}
