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

namespace App\Modules\Settings\MailAccount\Views;

class ListView extends \App\Modules\Settings\Base\Views\ListView
{
	public function getPageTitle(\App\Http\Vtiger_Request $request): string
	{
		return 'LBL_MAIL_ACCOUNTS';
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$scripts = parent::getFooterScripts($request);
		$jsScriptInstances = $this->checkAndConvertJsScripts([
			'modules.Settings.MailAccount.resources.List',
		]);

		return array_merge($scripts, $jsScriptInstances);
	}
}
