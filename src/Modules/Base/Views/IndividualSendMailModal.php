<?php

namespace App\Modules\Base\Views;

/**
 * Record-context mail compose modal (single + mass send).
 *
 * @package FreeCRM.ModalView
 * @license licenses/License.html
 * @author bmankowski@gmail.com
 */
class IndividualSendMailModal extends BasicModal
{
	public $fields = [];

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$currentUserPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPrivilegesModel->hasModulePermission($moduleName)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (!$request->isEmpty('sourceRecord') && !\App\Security\Privilege::isPermitted($request->get('sourceModule'), 'DetailView', $request->get('sourceRecord'))) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function getModalScripts(\App\Http\Vtiger_Request $request)
	{
		return array_merge(
			parent::getModalScripts($request),
			$this->checkAndConvertJsScripts([
				'modules.Mail.resources.SenderPicker',
				'modules.Mail.resources.ComposeAttachments',
			])
		);
	}

	public function getModalCss(\App\Http\Vtiger_Request $request)
	{
		return array_merge(
			parent::getModalCss($request),
			$this->checkAndConvertCssStyles(['modules.Mail.ComposeAttachments'])
		);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->preProcess($request);
		$viewer = $this->getViewer($request);
		$templateModule = $moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');
		if ($sourceModule && isset(\App\TextParser\TextParser::$sourceModules[$sourceModule]) && is_array(\App\TextParser\TextParser::$sourceModules[$sourceModule]) && in_array($moduleName, \App\TextParser\TextParser::$sourceModules[$sourceModule])) {
			$templateModule = $sourceModule;
		}
		$records = $this->getRecordsListFromRequest($request);
		$isMassSend = ($records['all'] ?? 0) > 1;
		$viewer->assign('IS_MASS_SEND', $isMassSend);
		$viewer->assign('FIELD_EMAILS', $this->getFieldEmailDisplayValues($request, $records));
		$userId = (int) $request->getUser()->getId();
		$rawTemplateList = \App\Email\Mail::getTempleteList($templateModule);
		$rawTemplateList = $this->filterTemplateListByProjectAccount($request, $templateModule, $rawTemplateList);
		$templateList = $this->filterTemplateList(
			$rawTemplateList,
			$this->parseTemplateIdsFromRequest($request)
		);
		foreach ($templateList as &$tpl) {
			$detail = \App\Email\Mail::getTempleteDetail($tpl['id']);
			$tpl['default_sender_ref'] = $detail
				? \App\Modules\Mail\Models\Module::defaultSenderRefForTemplate($detail, $userId)
				: '';
		}
		unset($tpl);
		$viewer->assign('TEMPLATE_MODULE', $templateModule);
		$viewer->assign('RECORDS', $records);
		$viewer->assign('FIELDS', $this->fields);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SELECTED_IDS', $request->get('selected_ids'));
		$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
		$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		$viewer->assign('TEMPLETE_LIST', $templateList);
		$viewer->assign('COMPOSE_SENDERS', \App\Modules\Mail\Models\Account::getComposeSenders($userId));
		$viewer->assign('CAN_SEND_MAIL', \App\Modules\Mail\Models\Module::canUserSend($userId));
		$initialField = $this->resolveInitialField($request, $records);
		$viewer->assign('INITIAL_FIELD', $initialField);
		$initialPreview = $isMassSend
			? ['success' => false]
			: $this->getInitialPreview($request, $records, $templateList, $initialField);
		$viewer->assign('INITIAL_PREVIEW', $initialPreview);
		$viewer->assign('USER_MODEL', $request->getUser());
		$this->assignComposeAttachmentLimits($viewer);
		$viewer->view('IndividualSendMailModal.tpl', $moduleName);
		$this->postProcess($request);
	}

	/**
	 * @return array<string, int>
	 */
	public function getRecordsListFromRequest(\App\Http\Vtiger_Request $request): array
	{
		$dataReader = $this->getQuery($request)->createCommand()->query();
		$count = ['all' => 0, 'emails' => 0];
		foreach ($this->fields as $fieldName => $fieldModel) {
			$count[$fieldName] = 0;
		}
		while ($row = $dataReader->read()) {
			$count['all'] += 1;
			foreach ($this->fields as $fieldName => $fieldModel) {
				if (!empty($row[$fieldName])) {
					$count[$fieldName] += 1;
					$count['emails'] += 1;
				}
			}
		}

		return $count;
	}

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
		$moduleModel = $queryGenerator->getModuleModel();
		$baseTableName = $moduleModel->get('basetable');
		$baseTableId = $moduleModel->get('basetableid');
		foreach ($moduleModel->getFieldsByType('email') as $fieldName => $fieldModel) {
			if ($fieldModel->isActiveField()) {
				$this->fields[$fieldName] = $fieldModel;
			}
		}
		$queryGenerator->setFields(array_merge(['id'], array_keys($this->fields)));
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

	protected function assignComposeAttachmentLimits(\App\Runtime\CRM_Viewer $viewer): void
	{
		$viewer->assign('MAIL_COMPOSE_ATTACHMENT_LIMITS', [
			'maxFileMb' => (int) \App\Core\AppConfig::module('Mail', 'attachment_max_size_mb'),
			'maxTotalMb' => (int) \App\Core\AppConfig::module('Mail', 'compose_max_total_mb'),
			'maxFiles' => (int) \App\Core\AppConfig::module('Mail', 'compose_max_files'),
			'maxFileBytes' => \App\Modules\Mail\Models\ComposeAttachment::maxFileBytes(),
			'maxTotalBytes' => \App\Modules\Mail\Models\ComposeAttachment::maxTotalBytes(),
		]);
	}

	/**
	 * @param array<string, int> $records
	 * @return array<string, string>
	 */
	private function getFieldEmailDisplayValues(\App\Http\Vtiger_Request $request, array $records): array
	{
		$fieldValues = [];
		foreach ($this->fields as $fieldName => $fieldModel) {
			$fieldValues[$fieldName] = [];
		}
		$dataReader = $this->getQuery($request)->createCommand()->query();
		while ($row = $dataReader->read()) {
			foreach ($this->fields as $fieldName => $fieldModel) {
				$email = trim((string) ($row[$fieldName] ?? ''));
				if ($email !== '') {
					$fieldValues[$fieldName][$email] = true;
				}
			}
		}
		$display = [];
		foreach ($this->fields as $fieldName => $fieldModel) {
			if (($records[$fieldName] ?? 0) !== 1) {
				continue;
			}
			$unique = array_keys($fieldValues[$fieldName]);
			if (\count($unique) === 1) {
				$display[$fieldName] = $unique[0];
			}
		}

		return $display;
	}

	private function resolveInitialField(\App\Http\Vtiger_Request $request, array $records): string
	{
		$to = strtolower(trim((string) $request->getByType('to', 'Email')));
		if ($to !== '') {
			$selectedRaw = $request->get('selected_ids');
			$selectedIds = \is_array($selectedRaw) ? $selectedRaw : (\App\Utils\Json::decode((string) $selectedRaw) ?: []);
			if (\is_array($selectedIds) && $selectedIds !== []) {
				$recordId = (int) reset($selectedIds);
				if ($recordId > 0) {
					try {
						$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $request->getModule());
						foreach ($this->fields as $fieldName => $fieldModel) {
							if (!$fieldModel->isActiveField()) {
								continue;
							}
							$value = strtolower(trim((string) $recordModel->get($fieldName)));
							if ($value !== '' && $value === $to) {
								return $fieldName;
							}
						}
					} catch (\Throwable) {
					}
				}
			}
		}
		foreach ($this->fields as $fieldName => $fieldModel) {
			if (!empty($records[$fieldName])) {
				return $fieldName;
			}
		}

		return '';
	}

	private function getInitialPreview(\App\Http\Vtiger_Request $request, array $records, array $templateList, string $field)
	{
		if ($field === '' || empty($templateList[0]['id'])) {
			return ['success' => false];
		}
		$row = $this->getQuery($request)->limit(1)->one();
		if (!$row || empty($row[$field])) {
			return ['success' => false];
		}
		$template = \App\Email\Mail::getTemplete($templateList[0]['id']);
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
				'subject' => $template['subject'] ?? '',
				'content' => \App\Utils\TemplateStyles::inlineEmailCss($template['content'] ?? ''),
				'missingSourceContext' => true,
				'warning' => 'Ten szablon wymaga kontekstu projektu (sourceRecord).',
			];
		}
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($row['id'], $request->getModule());
		$textParser = \App\TextParser\TextParser::getInstanceByModel($recordModel);
		$textParser->setParams([
			'template' => $templateList[0]['id'],
			'moduleName' => $request->getModule(),
			'recordId' => $row['id'],
			'to' => $row[$field],
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
			'subject' => $subject,
			'content' => \App\Utils\TemplateStyles::inlineEmailCss($content),
			'senderType' => \App\Modules\Mail\Models\Module::resolveSenderType($template),
			'templateSmtpId' => \App\Email\Mail::resolveTemplateSmtpId($template),
			'defaultSenderRef' => \App\Modules\Mail\Models\Module::defaultSenderRefForTemplate($template, $userId),
		];
	}

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
	 * @return list<int>
	 */
	private function parseTemplateIdsFromRequest(\App\Http\Vtiger_Request $request): array
	{
		$raw = $request->get('templateIds');
		if ($raw === null || $raw === '') {
			return [];
		}
		if (\is_string($raw)) {
			$decoded = json_decode($raw, true);
			if (\is_array($decoded)) {
				return array_values(array_filter(array_map('intval', $decoded)));
			}
			return array_values(array_filter(array_map('intval', explode(',', $raw))));
		}
		if (\is_array($raw)) {
			return array_values(array_filter(array_map('intval', $raw)));
		}

		return [];
	}

	/**
	 * @param array<int, array<string, mixed>> $templateList
	 * @param list<int> $allowedIds
	 * @return array<int, array<string, mixed>>
	 */
	private function filterTemplateList(array $templateList, array $allowedIds): array
	{
		if ($allowedIds === []) {
			return $templateList;
		}
		$byId = [];
		foreach ($templateList as $row) {
			if (!empty($row['id'])) {
				$byId[(int) $row['id']] = $row;
			}
		}
		$filtered = [];
		foreach ($allowedIds as $id) {
			$id = (int) $id;
			if ($id > 0 && isset($byId[$id])) {
				$filtered[] = $byId[$id];
			}
		}

		return $filtered;
	}

	/**
	 * @param array<int, array<string, mixed>> $templateList
	 * @return array<int, array<string, mixed>>
	 */
	private function filterTemplateListByProjectAccount(
		\App\Http\Vtiger_Request $request,
		string $templateModule,
		array $templateList
	): array {
		if ($templateModule !== 'ProjektyRekrutacyjne' || $templateList === []) {
			return $templateList;
		}
		$sourceRecord = $request->getInteger('sourceRecord');
		$sourceModule = $request->getByType('sourceModule', 2);
		if ($sourceRecord <= 0 || $sourceModule !== 'ProjektyRekrutacyjne') {
			return $templateList;
		}
		try {
			$project = \App\Modules\Base\Models\Record::getInstanceById($sourceRecord, 'ProjektyRekrutacyjne');
			$accountId = (int) $project->get('kontrahent');
		} catch (\Throwable) {
			return $templateList;
		}
		if ($accountId <= 0) {
			return [];
		}

		return \App\Modules\EmailTemplates\Models\RecruitmentTemplate::filterTemplateListForAccount(
			$templateList,
			$accountId
		);
	}

	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return $this->isMassSendRequest($request) ? '' : 'modal-full';
	}

	private function isMassSendRequest(\App\Http\Vtiger_Request $request): bool
	{
		$selected = $request->get('selected_ids');
		if ($selected === 'all') {
			return true;
		}
		if (\is_array($selected)) {
			return \count($selected) > 1;
		}
		if (\is_string($selected) && $selected !== '') {
			$decoded = \App\Utils\Json::decode($selected);
			if (\is_array($decoded)) {
				return \count($decoded) > 1;
			}

			return \count(array_filter(explode(',', $selected))) > 1;
		}

		return false;
	}
}
