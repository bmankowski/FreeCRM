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
		$templateList = \App\Email\Mail::getTempleteList($templateModule);
		$viewer->assign('TEMPLATE_MODULE', $templateModule);
		$viewer->assign('RECORDS', $records);
		$viewer->assign('FIELDS', $this->fields);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SELECTED_IDS', $request->get('selected_ids'));
		$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
		$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
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
		$subject = $textParser->setContent($template['subject'])->parse()->getContent();
		$content = $textParser->setContent($template['content'])->parse()->getContent();
		unset($textParser);
		return [
			'success' => true,
			'subject' => $subject,
			'content' => \App\Utils\TemplateStyles::inlineEmailCss($content),
		];
	}
	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return 'modal-full';
	}
}
