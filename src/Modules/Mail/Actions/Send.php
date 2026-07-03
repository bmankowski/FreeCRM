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

class Send extends Base
{
	public function checkPermission(\App\Http\Vtiger_Request $request): bool
	{
		if (!\App\Core\AppConfig::main('isActiveSendingMails')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		$sourceModule = $request->get('sourceModule');
		$sourceRecord = $request->get('sourceRecord');
		if ($sourceModule && $sourceRecord && !\App\Security\Privilege::isPermitted($sourceModule, 'DetailView', $sourceRecord)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$userId = (int) $request->getUser()->getId();
		$senderRef = (string) $request->get('senderRef');
		$templateId = (int) $request->get('templateId');
		$sourceModule = $request->getByType('sourceModule', 2);
		$sourceRecord = $request->getInteger('sourceRecord');

		$subject = $request->getByType('subject', 'Text');
		$content = $request->getForHtml('content');
		$toRaw = $request->get('to');
		$to = is_array($toRaw) ? $toRaw : array_filter(array_map('trim', explode(',', (string) $toRaw)));

		$params = [
			'subject' => $subject,
			'body_html' => $content,
			'to' => $to,
			'sourceModule' => $sourceModule,
			'sourceRecord' => $sourceRecord,
			'recordModule' => $request->getByType('recordModule', 2),
			'recordId' => $request->getInteger('recordId'),
		];

		try {
			if ($templateId > 0) {
				$template = \App\Email\Mail::getTemplete($templateId);
				if (!$template) {
					throw new \App\Exceptions\AppException('Template not found');
				}
				if ($senderRef === '') {
					throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_REQUIRED');
				}
				$params['template'] = $templateId;
				if ($subject !== '') {
					$params['subjectOverride'] = $subject;
				}
				$messageId = \App\Modules\Mail\Models\Outbound::sendFromTemplate($userId, $senderRef, $params, $template);
			} else {
				if ($senderRef === '') {
					throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_REQUIRED');
				}
				$messageId = \App\Modules\Mail\Models\Outbound::sendRaw($userId, $senderRef, $params);
			}
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(['success' => true, 'messageId' => $messageId]);
			$response->emit();
		} catch (\Throwable $e) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(['success' => false, 'error' => $e->getMessage()]);
			$response->emit();
		}
	}
}
