<?php

namespace App\Modules\Base\Views;

/**
 * Individual send mail modal class
 * @package FreeCRM.ModalView
 * @license licenses/License.html
 * @author bmankowski@gmail.com
 */
class IndividualSendMailModal extends SendMailModal
{
	/**
	 * Process function
	 * @param \App\Http\Vtiger_Request $request
	 */
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
		$templateList = $this->filterTemplateList(
			\App\Email\Mail::getTempleteList($templateModule),
			$this->parseTemplateIdsFromRequest($request)
		);
		$viewer->assign('TEMPLATE_MODULE', $templateModule);
		$viewer->assign('RECORDS', $records);
		$viewer->assign('FIELDS', $this->fields);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SELECTED_IDS', $request->get('selected_ids'));
		$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
		$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		$viewer->assign('TEMPLETE_LIST', $templateList);
		$viewer->assign('DEFAULT_SMTP', \App\Email\Mail::getDefaultSmtp());
		$viewer->assign('INITIAL_PREVIEW', $this->getInitialPreview($request, $records, $templateList));
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->view('IndividualSendMailModal.tpl', $moduleName);
		$this->postProcess($request);
	}

	/**
	 * Get initial preview for first available template and email field
	 * @param \App\Http\Vtiger_Request $request
	 * @param array $records
	 * @param array $templateList
	 * @return array
	 */
	private function getInitialPreview(\App\Http\Vtiger_Request $request, array $records, array $templateList)
	{
		$field = '';
		foreach ($this->fields as $fieldName => $fieldModel) {
			if (!empty($records[$fieldName])) {
				$field = $fieldName;
				break;
			}
		}
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
		unset($textParser);
		return [
			'success' => true,
			'subject' => $subject,
			'content' => \App\Utils\TemplateStyles::inlineEmailCss($content),
		];
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
		return str_contains($subject, '$(sourceRecord :') || str_contains($content, '$(sourceRecord :');
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

	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return 'modal-full';
	}
}
