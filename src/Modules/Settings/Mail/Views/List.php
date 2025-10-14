<?php

namespace FreeCRM\Modules\Settings\Mail\Views;



/**
 * List View Class for Mail Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class List extends \FreeCRM\Modules\Settings\Vtiger\Views\List
{

	/**
	 * Function to get the page title
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @return string
	 */
	public function getPageTitle(\FreeCRM\Http\Vtiger_Request $request)
	{
		return 'LBL_MAIL_QUEUE_PAGE_TITLE';
	}
}
