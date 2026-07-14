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

namespace App\Modules\DelayedEmails\Actions;

class Cancel extends \App\Modules\Base\Views\Basic
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$user = $request->getUser();
		if (!$user instanceof \App\Modules\Users\Models\Record || !$user->isAdminUser()) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$id = (int) $request->get('record');
		if ($id > 0) {
			\App\Email\Delayed\Buffer::cancelById($id);
		}
		header('Location: index.php?module=DelayedEmails&view=ListView');
	}

	public function validateRequest(\App\Http\Vtiger_Request $request): void
	{
		$request->validateReadAccess();
	}
}
