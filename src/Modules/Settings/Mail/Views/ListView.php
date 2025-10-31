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
}
