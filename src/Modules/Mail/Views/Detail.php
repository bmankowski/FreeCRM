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

namespace App\Modules\Mail\Views;

class Detail extends \App\Modules\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		$id = (int) $request->get('record');
		$message = \App\Modules\Mail\Models\Message::getById($id);
		if (!$message) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		$account = !empty($message['account_id']) ? \App\Modules\Mail\Models\Account::getById((int) $message['account_id']) : null;
		\App\Modules\Mail\Models\Acl::assert((int) $request->getUser()->getId(), \App\Modules\Mail\Models\Acl::ACTION_VIEW, [
			'message' => $message,
			'account' => $account,
		]);

		$config = \HTMLPurifier_Config::createDefault();
		$purifier = new \HTMLPurifier($config);
		$bodyHtml = !empty($message['body_html']) ? $purifier->purify($message['body_html']) : nl2br(htmlspecialchars((string) ($message['body_text'] ?? '')));

		$links = (new \App\Db\Query())->from('u_yf_mail_record_links')->where(['message_id' => $id])->all();
		$attachments = \App\Modules\Mail\Models\Attachment::getForMessage($id);

		$viewer = $this->getViewer($request);
		$viewer->assign('MESSAGE', $message);
		$viewer->assign('BODY_HTML', $bodyHtml);
		$viewer->assign('LINKS', $links);
		$viewer->assign('ATTACHMENTS', $attachments);
		$viewer->view('Detail.tpl', 'Mail');
	}
}
