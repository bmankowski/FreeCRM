<?php

namespace App\Modules\Workflow\Tasks;

use App\Modules\Workflow\RelationFieldResolver;
use App\Modules\Workflow\RelationWorkflowContext;
use App\Modules\Workflow\VTTask;

/**
 * Email Template Task Class
 * @package YetiForce.WorkflowTask
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class VTEmailTemplateTask extends VTTask
{

	/** @var bool Sending email takes more time, this should be handled via queue all the time. */
	public $executeImmediately = true;
	public $template;
	public $email;
	public $emailoptout;
	public $smtp;
	public $copy_email;

	/**
	 * Get field names
	 * @return string[]
	 */
	public function getFieldNames(): array
	{
		return ['template', 'email', 'emailoptout', 'smtp', 'copy_email'];
	}

	/**
	 * Execute task
	 * @param \App\Modules\Base\Models\Record $recordModel
	 */
	public function doTask($recordModel, ?RelationWorkflowContext $context = null)
	{
		if ($context !== null) {
			$this->doRelationTask($recordModel, $context);
			return;
		}

		if (!empty($this->template)) {
			$mailerContent = [];
			if (!empty($this->smtp)) {
				$mailerContent['smtp_id'] = $this->smtp;
			}
			$emailParser = \App\Email\EmailParser::getInstanceByModel($recordModel);
			$emailParser->emailoptout = $this->emailoptout ? true : false;
			if ($this->email) {
				$email = is_array($this->email) ? implode(',', $this->email) : $this->email;
				$mailerContent['to'] = $emailParser->setContent($email)->parse()->getContent(true);
			}
			unset($emailParser);
			if (empty($mailerContent['to'])) {
				return false;
			}
			if ($recordModel->getModuleName() === 'Contacts' && !$recordModel->isEmpty('notifilanguage')) {
				$mailerContent['language'] = $recordModel->get('notifilanguage');
			}
			$mailerContent['template'] = $this->template;
			$mailerContent['recordModel'] = $recordModel;
			if (!empty($this->copy_email)) {
				$mailerContent['bcc'] = $this->copy_email;
			}
			\App\Email\Mailer::sendFromTemplate($mailerContent);
		}
	}

	protected function doRelationTask(\App\Modules\Base\Models\Record $recordModel, RelationWorkflowContext $context): void
	{
		if (empty($this->template)) {
			return;
		}

		$resolver = new RelationFieldResolver($context);
		$toEmails = $this->resolveRelationRecipients($recordModel, $context, $resolver);
		if (empty($toEmails)) {
			return;
		}

		$template = \App\Email\Mail::getTemplete($this->template);
		if (!$template) {
			return;
		}

		$textParser = \App\TextParser\TextParser::getInstanceByModel($recordModel);
		$subject = $resolver->replaceInContent((string) ($template['subject'] ?? ''));
		$content = $resolver->replaceInContent((string) ($template['content'] ?? ''));
		$mailerContent = [
			'smtp_id' => !empty($this->smtp) ? $this->smtp : \App\Email\Mail::getDefaultSmtp(),
			'to' => $toEmails,
			'subject' => $textParser->setContent($subject)->parse()->getContent(),
			'content' => $textParser->setContent($content)->parse()->getContent(),
		];
		unset($textParser);

		$destModel = $context->getDestinationRecordModel();
		if ($destModel->getModuleName() === 'Contacts' && !$destModel->isEmpty('notifilanguage')) {
			$mailerContent['language'] = $destModel->get('notifilanguage');
		} elseif ($recordModel->getModuleName() === 'Contacts' && !$recordModel->isEmpty('notifilanguage')) {
			$mailerContent['language'] = $recordModel->get('notifilanguage');
		}

		if (!empty($this->copy_email)) {
			$bccEmails = $this->parseEmailsFromString($resolver->replaceInContent($this->copy_email));
			if ($bccEmails) {
				$mailerContent['bcc'] = $bccEmails;
			}
		}

		if (isset($template['attachments'])) {
			$mailerContent['attachments'] = $template['attachments'];
		}

		if (!empty($mailerContent['content'])) {
			\App\Email\Delayed\Buffer::enqueueFromMailerContent(
				$context->getSourceRecordId(),
				$context->getDestinationRecordId(),
				\App\Email\Delayed\DelayedEmailType::STATUS_CHANGE,
				$mailerContent
			);
		}
	}

	/**
	 * @return array<string, string>|array<int, string>
	 */
	protected function resolveRelationRecipients(
		\App\Modules\Base\Models\Record $recordModel,
		RelationWorkflowContext $context,
		RelationFieldResolver $resolver
	): array {
		if (empty($this->email)) {
			return [];
		}

		$emailContent = is_array($this->email) ? implode(',', $this->email) : (string) $this->email;
		$emailContent = $resolver->replaceInContent($emailContent);

		$destModel = $context->getDestinationRecordModel();
		$emailParser = \App\Email\EmailParser::getInstanceByModel($destModel);
		$emailParser->emailoptout = (bool) $this->emailoptout;
		$toEmails = $emailParser->setContent($emailContent)->parse()->getContent(true);
		unset($emailParser);

		if (empty($toEmails)) {
			$emailParser = \App\Email\EmailParser::getInstanceByModel($recordModel);
			$emailParser->emailoptout = (bool) $this->emailoptout;
			$toEmails = $emailParser->setContent($emailContent)->parse()->getContent(true);
			unset($emailParser);
		}

		return is_array($toEmails) ? $toEmails : [];
	}

	/**
	 * @return array<string, string>
	 */
	protected function parseEmailsFromString(string $raw): array
	{
		$raw = trim($raw);
		if ($raw === '' || $raw === '-') {
			return [];
		}
		$emails = [];
		foreach (explode(',', $raw) as $part) {
			$part = trim($part);
			if ($part === '' || !filter_var($part, FILTER_VALIDATE_EMAIL)) {
				continue;
			}
			$emails[$part] = '';
		}
		return $emails;
	}
}
