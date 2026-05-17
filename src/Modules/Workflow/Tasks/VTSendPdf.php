<?php

namespace App\Modules\Workflow\Tasks;

use App\Modules\Workflow\VTTask;

/**
 * Email PDF Template Task Class
 * @package YetiForce.WorkflowTask
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class VTSendPdf extends VTTask
{

	/** @var bool Sending email takes more time, this should be handled via queue all the time. */
	public $executeImmediately = true;

	/**
	 * Get field names
	 * @return string[]
	 */
	public function getFieldNames(): array
	{
		return ['documentTemplate', 'mailTemplate', 'email', 'emailoptout', 'smtp', 'copy_email'];
	}

	/**
	 * Execute task
	 * @param \App\Modules\Base\Models\Record $recordModel
	 */
	public function doTask($recordModel)
	{
		$documentTemplateId = $this->documentTemplate ?? null;
		if (!empty($this->mailTemplate) && !empty($documentTemplateId)) {
			$mailerContent = [];
			if (!empty($this->smtp)) {
				$mailerContent['smtp_id'] = $this->smtp;
			}
			$emailParser = \App\EmailParser::getInstanceByModel($recordModel);
			$emailParser->emailoptout = $this->emailoptout ? true : false;
			if ($this->email) {
				$mailerContent['to'] = $emailParser->setContent(implode(',', $this->email))->parse()->getContent(true);
			}
			unset($emailParser);
			if (empty($mailerContent['to'])) {
				return false;
			}
			if ($recordModel->getModuleName() === 'Contacts' && !$recordModel->isEmpty('notifilanguage')) {
				$mailerContent['language'] = $recordModel->get('notifilanguage');
			}
			$mailerContent['template'] = $this->mailTemplate;
			$mailerContent['recordModel'] = $recordModel;
			if (!empty($this->copy_email)) {
				$mailerContent['bcc'] = $this->copy_email;
			}
			$templateRecord = \App\Modules\Base\Models\PDF::getInstanceById($documentTemplateId);
			$fileName = \vtlib\Functions:: slug($templateRecord->getName()) . '_' . time() . '.pdf';
			$pdfFile = 'cache' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . $fileName;
			\App\Modules\Base\Models\PDF::exportToPdf($recordModel->getId(), $recordModel->getModuleName(), $documentTemplateId, $pdfFile, 'F');
			if (!file_exists($pdfFile)) {
				\App\Log\Log::error('An error occurred while generating PFD file, the file doesn\'t exist. Sending email with PDF has been blocked.');
				return false;
			}
			if (!$templateRecord->isEmpty('filename')) {
				$fileName = $templateRecord->get('filename');
			}
			$mailerContent['attachments'] = [$pdfFile => $fileName];
			\App\Email\Mailer::sendFromTemplate($mailerContent);
		}
	}
}
