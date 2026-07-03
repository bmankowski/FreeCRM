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

class UploadComposeAttachment extends Base
{
	public function checkPermission(\App\Http\Vtiger_Request $request): bool
	{
		if (!\App\Core\AppConfig::main('isActiveSendingMails')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		$moduleName = $request->get('sourceModule') ?: $request->getModule();
		if ($moduleName && !\App\Security\Privilege::isPermitted($moduleName)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		return true;
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$userId = (int) $request->getUser()->getId();
		$file = $_FILES['file'] ?? null;
		if (!is_array($file) || empty($file['tmp_name'])) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_INVALID');
		}
		\App\Modules\Mail\Models\ComposeAttachment::cleanupExpired();
		if (\App\Modules\Mail\Models\ComposeAttachment::countUserTokens($userId) >= \App\Modules\Mail\Models\ComposeAttachment::maxFiles()) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_MAX_FILES');
		}
		$newSize = (int) ($file['size'] ?? 0);
		$totalAfter = \App\Modules\Mail\Models\ComposeAttachment::totalUserBytes($userId) + $newSize;
		if ($totalAfter > \App\Modules\Mail\Models\ComposeAttachment::maxTotalBytes()) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_TOTAL_TOO_LARGE');
		}

		$result = \App\Modules\Mail\Models\ComposeAttachment::stageUpload($userId, $file);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
