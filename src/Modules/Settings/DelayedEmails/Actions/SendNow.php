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

namespace App\Modules\Settings\DelayedEmails\Actions;

class SendNow extends \App\Modules\Settings\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdmin()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$id = (int) $request->get('record');
		if ($id > 0) {
			\App\Email\Delayed\Buffer::sendNow($id);
		}
		header('Location: index.php?module=DelayedEmails&parent=Settings&view=ListView');
	}

	public function validateRequest(\App\Http\Vtiger_Request $request): void
	{
		$request->validateReadAccess();
	}
}
