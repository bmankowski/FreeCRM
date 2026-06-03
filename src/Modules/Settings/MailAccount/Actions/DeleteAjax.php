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

namespace App\Modules\Settings\MailAccount\Actions;

class DeleteAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$recordId = (int) $request->get('record');
		\App\Modules\Mail\Models\Account::deleteAccount($recordId);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}
