<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Settings\DelayedEmails\Views;

class ListView extends \App\Modules\Settings\Base\Views\ListView
{
	public function getPageTitle(\App\Http\Vtiger_Request $request): string
	{
		return 'LBL_DELAYED_EMAILS';
	}

	protected function prepareListViewData(\App\Http\Vtiger_Request $request): void
	{
		if (empty($request->get('orderby'))) {
			$request->set('orderby', 'send_after');
			$request->set('sortorder', 'ASC');
		}
		parent::prepareListViewData($request);
	}
}
