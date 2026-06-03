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

class Send extends \App\Base\Controllers\BaseActionController
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

		$template = $templateId ? \App\Email\Mail::getTemplete($templateId) : null;
		if ($template && ($subject === '' || $content === '')) {
			$parsed = $this->parseTemplate($template, $request, $to[0] ?? '');
			$subject = $subject !== '' ? $subject : $parsed['subject'];
			$content = $content !== '' ? $content : $parsed['content'];
		}

		$params = [
			'subject' => $subject,
			'body_html' => $content,
			'to' => $to,
			'sourceModule' => $sourceModule,
			'sourceRecord' => $sourceRecord,
		];

		try {
			if (str_starts_with($senderRef, 'account:')) {
				$accountId = (int) substr($senderRef, 8);
				$messageId = \App\Modules\Mail\Models\Outbound::sendViaAccount($userId, $accountId, $params);
			} elseif (str_starts_with($senderRef, 'smtp:')) {
				$smtpId = (int) substr($senderRef, 5);
				\App\Email\Mailer::addMail([
					'smtp_id' => $smtpId,
					'subject' => $subject,
					'content' => $content,
					'to' => $to,
					'source_module' => $sourceModule,
					'source_id' => $sourceRecord,
				]);
				$messageId = \App\Modules\Mail\Models\Outbound::recordSystemSend($userId, $smtpId, $params);
			} else {
				throw new \App\Exceptions\AppException('Invalid sender');
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

	/**
	 * @param list<string> $to
	 * @return array{subject: string, content: string}
	 */
	private function parseTemplate(array $template, \App\Http\Vtiger_Request $request, string $to): array
	{
		$moduleName = $request->getByType('recordModule', 2) ?: $request->getByType('sourceModule', 2);
		$recordId = $request->getInteger('recordId') ?: $request->getInteger('sourceRecord');
		$recordModel = $recordId ? \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName) : null;
		$textParser = $recordModel
			? \App\TextParser\TextParser::getInstanceByModel($recordModel)
			: \App\TextParser\TextParser::getInstance($moduleName);
		$textParser->setParams([
			'template' => $template['emailtemplatesid'] ?? $template['id'] ?? null,
			'moduleName' => $moduleName,
			'recordId' => $recordId,
			'to' => $to,
			'sourceModule' => $request->get('sourceModule'),
			'sourceRecord' => $request->get('sourceRecord'),
		]);
		if ($request->getInteger('sourceRecord') && $request->get('sourceModule')) {
			$textParser->setSourceRecord($request->getInteger('sourceRecord'), $request->getByType('sourceModule', 2));
		}
		$subject = $textParser->setContent($template['subject'] ?? '')->parse()->getContent();
		$content = $textParser->setContent($template['content'] ?? '')->parse()->getContent();
		return ['subject' => $subject, 'content' => $content];
	}
}
