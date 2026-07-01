<?php

namespace App\Modules\Workflow\Tasks;

use App\Modules\Workflow\RelationFieldResolver;
use App\Modules\Workflow\RelationWorkflowContext;
use App\Modules\Workflow\VTTask;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class VTEmailTask extends VTTask
{

	// Sending email takes more time, this should be handled via queue all the time.
	public $executeImmediately = true;
	public $recepient;
	public $subject;
	public $content;
	public $emailcc;
	public $emailbcc;
	public $fromEmail;
	public $smtp;
	public $emailoptout;

	public function getFieldNames(): array
	{
		return array('subject', 'content', 'recepient', 'emailcc', 'emailbcc', 'fromEmail', 'smtp', 'emailoptout');
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

		$mailerContent = [
			'smtp_id' => ($this->smtp) ? $this->smtp : \App\Email\Mail::getDefaultSmtp(),
		];
		$emailParser = \App\Email\EmailParser::getInstanceByModel($recordModel);
		$emailParser->emailoptout = $this->emailoptout ? true : false;
		if ($this->fromEmail) {
			$fromEmailDetails = $emailParser->setContent($this->fromEmail)->parse()->getContent(true);
			if ($fromEmailDetails) {
				foreach ($fromEmailDetails as $key => $value) {
					$mailerContent['from'] = ['email' => $key, 'name' => $value];
				}
			}
		}
		$toEmail = $emailParser->setContent($this->recepient)->parse()->getContent(true);
		if ($toEmail) {
			$mailerContent['to'] = $toEmail;
		}
		$ccEmail = $emailParser->setContent($this->emailcc)->parse()->getContent(true);
		if ($ccEmail) {
			$mailerContent['cc'] = $ccEmail;
		}
		$bccEmail = $emailParser->setContent($this->emailbcc)->parse()->getContent(true);
		if ($bccEmail) {
			$mailerContent['bcc'] = $bccEmail;
		}
		unset($emailParser);
		if (empty($toEmail) && empty($ccEmail) && empty($bccEmail)) {
			return false;
		}
		$textParser = \App\TextParser\TextParser::getInstanceByModel($recordModel);
		$mailerContent['subject'] = $textParser->setContent($this->subject)->parse()->getContent();
		$mailerContent['content'] = $textParser->setContent($this->content)->parse()->getContent();
		if (!empty($mailerContent['content'])) {
			\App\Email\Mailer::addMail(\App\Email\Mailer::withSmtpSenderRef($mailerContent));
		}
		unset($textParser);
	}

	protected function doRelationTask(\App\Modules\Base\Models\Record $recordModel, RelationWorkflowContext $context): void
	{
		$resolver = new RelationFieldResolver($context);
		$mailerContent = [
			'smtp_id' => ($this->smtp) ? $this->smtp : \App\Email\Mail::getDefaultSmtp(),
		];

		if ($this->fromEmail) {
			$fromRaw = $resolver->replaceInContent($this->fromEmail);
			$fromParsed = $this->parseEmailsFromString($fromRaw);
			if ($fromParsed) {
				$mailerContent['from'] = $fromParsed;
			}
		}

		$toRaw = $resolver->replaceInContent($this->recepient);
		$toEmail = $this->parseEmailsFromString($toRaw);
		if ($toEmail) {
			$mailerContent['to'] = $toEmail;
		}
		$ccRaw = $resolver->replaceInContent($this->emailcc ?? '');
		$ccEmail = $this->parseEmailsFromString($ccRaw);
		if ($ccEmail) {
			$mailerContent['cc'] = $ccEmail;
		}
		$bccRaw = $resolver->replaceInContent($this->emailbcc ?? '');
		$bccEmail = $this->parseEmailsFromString($bccRaw);
		if ($bccEmail) {
			$mailerContent['bcc'] = $bccEmail;
		}
		if (empty($toEmail) && empty($ccEmail) && empty($bccEmail)) {
			return;
		}

		$textParser = \App\TextParser\TextParser::getInstanceByModel($recordModel);
		$mailerContent['subject'] = $textParser->setContent($resolver->replaceInContent($this->subject))->parse()->getContent();
		$mailerContent['content'] = $textParser->setContent($resolver->replaceInContent($this->content))->parse()->getContent();
		if (!empty($mailerContent['content'])) {
			$mailerContent = \App\Email\Mailer::withSmtpSenderRef($mailerContent);
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
