<?php

namespace App\Modules\Base\Dashboards;

use App\Http\Vtiger_Request;

class MailsList extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request, $widget = NULL)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$userId = (int) $request->getUser()->getId();
		$accounts = \App\Modules\Mail\Models\Service::getUserAccounts($userId);
		$accountList = [];
		foreach ($accounts as $account) {
			$accountList[] = [
				'user_id' => $account['id'],
				'username' => $account['name'] ?? $account['username'],
			];
		}
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('WIDGET', \App\Modules\Base\Models\Widget::getInstance($request->get('linkid'), $userId));
		$viewer->assign('USER', $request->get('user'));
		$viewer->assign('ACCOUNTSLIST', $accountList);
		$viewer->assign('DATA', $request->getAll());
		if (!empty($request->get('content'))) {
			$viewer->view('dashboards/MailsListContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/MailsList.tpl', $moduleName);
		}
	}
}
