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

namespace App\Modules\Mail\Actions;

class ResetComposeAttachment extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request): bool
	{
		if (!\App\Core\AppConfig::main('isActiveSendingMails')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		return true;
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$userId = (int) $request->getUser()->getId();
		\App\Modules\Mail\Models\ComposeAttachment::cleanupExpired();
		\App\Modules\Mail\Models\ComposeAttachment::clearUserStaging($userId);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
