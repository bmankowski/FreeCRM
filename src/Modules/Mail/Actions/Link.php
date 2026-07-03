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

class Link extends Base
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		$userId = (int) $request->getUser()->getId();
		$messageId = (int) $request->get('messageId');
		$module = $request->getByType('crmModule', 2);
		$recordId = (int) $request->get('crmRecordId');

		$message = \App\Modules\Mail\Models\Message::getById($messageId);
		if (!$message) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		$account = !empty($message['account_id']) ? \App\Modules\Mail\Models\Account::getById((int) $message['account_id']) : null;
		\App\Modules\Mail\Models\Acl::assert($userId, \App\Modules\Mail\Models\Acl::ACTION_VIEW, [
			'message' => $message,
			'account' => $account,
		]);
		if (!\App\Security\Privilege::isPermitted($module, 'DetailView', $recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}

		$linked = \App\Modules\Mail\Models\Binding\Engine::link($messageId, $module, $recordId, 'manual', null);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => $linked]);
		$response->emit();
	}
}
