<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Settings\MailAccount\Views;

class ListView extends \App\Modules\Settings\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('ACCOUNTS', \App\Modules\Mail\Models\Account::listAllForAdmin());
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->assign('MODULE', $request->getModule());
		$viewer->view('List.tpl', $request->getModule(false));
	}
}
