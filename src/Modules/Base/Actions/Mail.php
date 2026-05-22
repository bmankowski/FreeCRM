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
			foreach ($rows as $row) {
				if ($sendEditedContent) {
					$templateDetail = \App\Email\Mail::getTemplete($template);
					$mailParams = [
						'template' => $template,
						'moduleName' => $moduleName,
						'recordId' => $row['id'],
						'to' => $row[$field],
						'sourceModule' => $sourceModule,
						'sourceRecord' => $sourceRecord,
						'smtp_id' => \App\Email\Mail::resolveTemplateSmtpId($templateDetail),
						'subject' => $subject,
						'content' => $content,
					];
					if (isset($templateDetail['attachments'])) {
						$mailParams['attachments'] = $templateDetail['attachments'];
					}
					\App\Email\Mailer::addMail(array_intersect_key($mailParams, array_flip(\App\Email\Mailer::$quoteColumn)));
					$result = true;
				} else {
					$result = \App\Email\Mailer::sendFromTemplate([
						'template' => $template,
						'moduleName' => $moduleName,
						'recordId' => $row['id'],
						'to' => $row[$field],
						'sourceModule' => $sourceModule,
						'sourceRecord' => $sourceRecord,
					]);
				}
				if (!$result) {
					break;
				}
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
		$subject = $textParser->setContent($template['subject'])->parse()->getContent();
		$content = $textParser->setContent($template['content'])->parse()->getContent();
		unset($textParser);
		return [
			'success' => true,
			'recordId' => $recordId,
			'to' => $recipient,
			'subject' => $subject,
			'content' => \App\Utils\TemplateStyles::inlineEmailCss($content),
		];
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
		if ($sourceModule) {
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
}
