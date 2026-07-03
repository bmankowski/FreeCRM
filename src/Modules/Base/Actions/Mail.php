<?php

namespace App\Modules\Base\Actions;

/**
 * Mail action class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Mail extends \App\Base\Controllers\BaseActionController
{

	/**
	 * Checking permissions
	 * @param \App\Http\Vtiger_Request $request
	 * @return boolean
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if (!\App\Security\Privilege::isPermitted($moduleName)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (!$request->isEmpty('sourceRecord') && !\App\Security\Privilege::isPermitted($request->get('sourceModule'), 'DetailView', $request->get('sourceRecord'))) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		return true;
	}

	/**
	 * Construct
	 */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('checkSmtp');
		$this->exposeMethod('previewMail');
		$this->exposeMethod('sendMails');
	}

	/**
	 * Process function
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
		}
	}

	/**
	 * Check if smtps are active
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function checkSmtp(\App\Http\Vtiger_Request $request)
	{
		$result = false;
		if (\App\Core\AppConfig::main('isActiveSendingMails')) {
			$result = !empty(\App\Email\Mail::getAll());
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Send mails
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function sendMails(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$field = $request->get('field');
		$template = $request->get('template');
		$sourceModule = $request->get('sourceModule');
		$sourceRecord = $request->get('sourceRecord');
		$result = false;
		if (!empty($template) && !empty($field)) {
			$dataReader = $this->getQuery($request)->createCommand()->query();
			$rows = [];
			while ($row = $dataReader->read()) {
				$rows[] = $row;
			}
			$subject = $request->getByType('subject', 'Text');
			$content = $request->getForHtml('content');
			$sendEditedContent = $subject !== '' && $content !== '' && count($rows) === 1;
			$userId = (int) $request->getUser()->getId();
			$attachmentTokens = self::parseAttachmentTokens($request);
			$userAttachments = $attachmentTokens !== []
				? \App\Modules\Mail\Models\ComposeAttachment::resolveTokens($userId, $attachmentTokens)
				: [];
			foreach ($rows as $row) {
				$templateDetail = \App\Email\Mail::getTemplete($template) ?: [];
				$senderRef = (string) $request->get('senderRef');
				if ($senderRef === '') {
					throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_REQUIRED');
				}
				if (!\App\Modules\Mail\Models\Module::userCanSendTemplate($userId, $templateDetail)) {
					\App\Log\Log::warning(
						'sendMails aborted: invalid sender for template (senderRef=' . $senderRef . ')',
						'Mail'
					);
					throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_INVALID');
				}
				$mailParams = [
					'template' => $template,
					'moduleName' => $moduleName,
					'recordId' => $row['id'],
					'field' => $field,
					'to' => self::normalizeRecipientList($row[$field]),
					'sourceModule' => $sourceModule,
					'sourceRecord' => $sourceRecord,
					'senderRef' => $senderRef,
				];
				if ($sendEditedContent && $subject !== '') {
					$mailParams['subjectOverride'] = $subject;
				}
				if ($sendEditedContent && $content !== '') {
					$mailParams['contentOverride'] = $content;
				}
				if ($userAttachments !== []) {
					$mailParams['attachments'] = $userAttachments;
				}
				$result = \App\Modules\Mail\Models\Outbound::sendFromTemplateParams($mailParams);
				if (!$result) {
					break;
				}
			}
			if ($attachmentTokens !== []) {
				\App\Modules\Mail\Models\ComposeAttachment::deleteTokens($userId, $attachmentTokens);
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Preview selected template with the first record from current selection
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function previewMail(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$field = $request->get('field');
		$template = $request->get('template');
		$result = ['success' => false];
		if (!empty($template) && !empty($field)) {
			$row = $this->getQuery($request)->limit(1)->one();
			if ($row) {
				$result = $this->getPreviewFromTemplate($template, $moduleName, $row['id'], $row[$field], $request);
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Build parsed subject and content for a template preview
	 * @param int|string $templateId
	 * @param string $moduleName
	 * @param int $recordId
	 * @param string $recipient
	 * @param \App\Http\Vtiger_Request $request
	 * @return array
	 */
	private function getPreviewFromTemplate($templateId, $moduleName, $recordId, $recipient, \App\Http\Vtiger_Request $request)
	{
		$template = \App\Email\Mail::getTemplete($templateId);
		if (!$template) {
			return ['success' => false];
		}
		$sourceRecord = $request->getInteger('sourceRecord');
		$sourceModule = $request->getByType('sourceModule', 2);
		$requiresSourceContext = $this->templateRequiresSourceContext($template);
		$hasSourceContext = $sourceRecord && !empty($sourceModule);
		if ($requiresSourceContext && !$hasSourceContext) {
			return [
				'success' => true,
				'recordId' => $recordId,
				'to' => $recipient,
				'subject' => $template['subject'] ?? '',
				'content' => \App\Utils\TemplateStyles::inlineEmailCss($template['content'] ?? ''),
				'missingSourceContext' => true,
				'warning' => 'Ten szablon wymaga kontekstu projektu (sourceRecord).',
				'templateAttachments' => \App\Modules\Mail\Models\ComposeAttachment::getTemplateAttachmentMeta((int) $templateId),
			];
		}
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		$textParser = \App\TextParser\TextParser::getInstanceByModel($recordModel);
		$textParser->setParams([
			'template' => $templateId,
			'moduleName' => $moduleName,
			'recordId' => $recordId,
			'to' => $recipient,
			'sourceModule' => $request->get('sourceModule'),
			'sourceRecord' => $request->get('sourceRecord'),
		]);
		if ($hasSourceContext) {
			$textParser->setSourceRecord($sourceRecord, $sourceModule);
		}
		$subject = $textParser->setContent($template['subject'])->parse()->getContent();
		$content = $textParser->setContent($template['content'])->parse()->getContent();
		$content = \App\Email\Mail::appendParsedFooter($content, $template, $textParser);
		unset($textParser);
		$userId = (int) $request->getUser()->getId();

		return [
			'success' => true,
			'recordId' => $recordId,
			'to' => $recipient,
			'subject' => $subject,
			'content' => \App\Utils\TemplateStyles::inlineEmailCss($content),
			'senderType' => \App\Modules\Mail\Models\Module::resolveSenderType($template),
			'templateSmtpId' => \App\Email\Mail::resolveTemplateSmtpId($template),
			'defaultSenderRef' => \App\Modules\Mail\Models\Module::defaultSenderRefForTemplate($template, $userId),
			'templateAttachments' => \App\Modules\Mail\Models\ComposeAttachment::getTemplateAttachmentMeta((int) $templateId),
		];
	}

	/**
	 * @return list<string>
	 */
	private static function parseAttachmentTokens(\App\Http\Vtiger_Request $request): array
	{
		$raw = $request->get('attachmentTokens');
		if ($raw === null || $raw === '') {
			return [];
		}
		if (is_array($raw)) {
			return array_values(array_filter(array_map('strval', $raw)));
		}
		$decoded = \App\Utils\Json::decode((string) $raw);
		if (is_array($decoded)) {
			return array_values(array_filter(array_map('strval', $decoded)));
		}

		return [];
	}

	/**
	 * Check whether template uses sourceRecord token.
	 *
	 * @param array $template
	 * @return bool
	 */
	private function templateRequiresSourceContext(array $template): bool
	{
		$subject = (string) ($template['subject'] ?? '');
		$content = (string) ($template['content'] ?? '');
		$footer = (string) ($template['footer'] ?? '');
		return str_contains($subject, '$(sourceRecord :')
			|| str_contains($content, '$(sourceRecord :')
			|| str_contains($footer, '$(sourceRecord :');
	}

	/**
	 * Get query instance
	 * @param \App\Http\Vtiger_Request $request
	 * @return \App\Db\Query
	 */
	public function getQuery(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');
		if ($sourceModule && $sourceModule !== $moduleName) {
			$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($request->get('sourceRecord'), $sourceModule);
			$listView = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $moduleName);
		} else {
			$listView = \App\Modules\Base\Models\ListView::getInstance($moduleName, $request->get('viewname'));
		}
		$searchResult = $request->get('searchResult');
		if (!empty($searchResult)) {
			$listView->set('searchResult', $searchResult);
		}
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($searchKey) && !empty($searchValue)) {
			$listView->set('operator', $operator);
			$listView->set('search_key', $searchKey);
			$listView->set('search_value', $searchValue);
		}
		$searchParams = $request->get('search_params');
		if (!empty($searchParams) && is_array($searchParams)) {
			$transformedSearchParams = $listView->getQueryGenerator()->parseBaseSearchParamsToCondition($searchParams);
			$listView->set('search_params', $transformedSearchParams);
		}
		$queryGenerator = $listView->getQueryGenerator();
		/** @var \App\Modules\Base\Models\Module $moduleModel */
		$moduleModel = $queryGenerator->getModuleModel();
		$baseTableName = $moduleModel->get('basetable');
		$baseTableId = $moduleModel->get('basetableid');
		$queryGenerator->setFields(['id', $request->get('field')]);
		$queryGenerator->addCondition($request->get('field'), '', 'ny');
		$selected = $request->get('selected_ids');
		if ($selected && $selected !== 'all') {
			$queryGenerator->addNativeCondition(["$baseTableName.$baseTableId" => $selected]);
		}
		$excluded = $request->get('excluded_ids');
		if ($excluded) {
			$queryGenerator->addNativeCondition(['not in', "$baseTableName.$baseTableId" => $excluded]);
		}
		return $queryGenerator->createQuery();
	}

	/**
	 * @param mixed $recipient
	 * @return list<string>
	 */
	private static function normalizeRecipientList(mixed $recipient): array
	{
		if (is_array($recipient)) {
			return array_values(array_filter(array_map('trim', $recipient)));
		}
		$recipient = trim((string) $recipient);
		if ($recipient === '') {
			return [];
		}

		return array_values(array_filter(array_map('trim', explode(',', $recipient))));
	}
}
