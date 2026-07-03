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

class Unlink extends Base
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		$userId = (int) $request->getUser()->getId();
		$linkId = (int) $request->get('linkId');
		$link = (new \App\Db\Query())->from('u_yf_mail_record_links')->where(['id' => $linkId])->one();
		if (!$link) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		$message = \App\Modules\Mail\Models\Message::getById((int) $link['message_id']);
		if (!$message) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		$account = !empty($message['account_id']) ? \App\Modules\Mail\Models\Account::getById((int) $message['account_id']) : null;
		\App\Modules\Mail\Models\Acl::assert($userId, \App\Modules\Mail\Models\Acl::ACTION_VIEW, [
			'message' => $message,
			'account' => $account,
		]);

		$deleted = \App\Modules\Mail\Models\Binding\Engine::unlink($linkId);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => $deleted]);
		$response->emit();
	}
}
