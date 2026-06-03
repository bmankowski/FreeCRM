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

class Logs extends \App\Modules\Settings\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$accountId = $request->getInteger('accountId');
		$query = (new \App\Db\Query())
			->from('u_yf_mail_log')
			->orderBy(['id' => SORT_DESC])
			->limit(500);
		if ($accountId > 0) {
			$query->where(['account_id' => $accountId]);
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('LOGS', $query->all());
		$viewer->assign('ACCOUNT_ID', $accountId);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->view('Logs.tpl', $request->getModule(false));
	}
}
