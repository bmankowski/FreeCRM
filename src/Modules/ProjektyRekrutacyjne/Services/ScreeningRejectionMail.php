<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\ProjektyRekrutacyjne\Services;

use App\Email\Delayed\Buffer;
use App\Email\Delayed\DelayedEmailType;
use App\Modules\EmailTemplates\Models\TemplateModule;

/**
 * Maps screening rejection reasons to Candidates email templates (sys_name).
 */
class ScreeningRejectionMail
{
	public const CANDIDATES_MODULE = 'Candidates';

	public const PROJECT_MODULE = 'ProjektyRekrutacyjne';

	public const REASON_SYS_NAMES = [
		'NO_EXPERIENCE' => 'kandydaci_odrzucenie_brak_doswiadczenia',
		'MISSING_SKILLS' => 'kandydaci_odrzucenie_brak_kompetencji',
		'PROFILE_FIT' => 'kandydaci_odrzucenie_niedopasowanie_profilu',
		'MISSING_POLISH_LANGUAGE' => 'kandydaci_odrzucenie_brak_jezyka_polskiego',
		'OTHER_CANDIDATE_CHOSEN' => 'kandydaci_odrzucenie_inny_kandydat',
		'PROJECT_CLOSED' => 'kandydaci_odrzucenie_proces_zamkniety',
	];

	public static function isKnownReason(string $reason): bool
	{
		return isset(self::REASON_SYS_NAMES[$reason]);
	}

	public static function resolveTemplateId(string $reason): ?int
	{
		$sysName = self::REASON_SYS_NAMES[$reason] ?? '';
		if ($sysName === '') {
			return null;
		}

		$id = (new \App\Db\Query())
			->select(['t.emailtemplatesid'])
			->from(['t' => 'u_yf_emailtemplates'])
			->innerJoin('vtiger_crmentity', 't.emailtemplatesid = vtiger_crmentity.crmid')
			->where([
				'vtiger_crmentity.deleted' => 0,
				't.sys_name' => $sysName,
			])
			->andWhere(TemplateModule::sqlMatchesColumn('t.modules', self::CANDIDATES_MODULE))
			->scalar();

		$templateId = (int) $id;

		return $templateId > 0 ? $templateId : null;
	}

	/**
	 * @return array{delayedMail?: array{enqueued: bool}}
	 */
	public static function enqueueDelayedRejectionMail(
		int $candidateId,
		int $projectId,
		string $rejectionReason,
		int $userId
	): array {
		if ($candidateId <= 0 || $projectId <= 0 || $userId <= 0 || !self::isKnownReason($rejectionReason)) {
			return [];
		}

		if (!\App\Core\AppConfig::main('isActiveSendingMails') || !\App\Email\Mail::getDefaultSmtp()) {
			return [];
		}

		if (!\App\Modules\Candidates\Models\RelatedListLeftSideEmail::recordHasEmail($candidateId)) {
			return [];
		}

		$templateId = self::resolveTemplateId($rejectionReason);
		if ($templateId === null) {
			return [];
		}

		$template = \App\Email\Mail::getTemplete($templateId) ?: [];
		if ($template === []) {
			return [];
		}

		$recipient = \App\Modules\Candidates\Models\RelatedListLeftSideEmail::resolvePrimaryEmailField($candidateId);
		if ($recipient === null) {
			return [];
		}

		try {
			$senderRef = \App\Modules\Mail\Models\Module::requireSenderRefForTemplate($template, $userId);
		} catch (\App\Exceptions\AppException $e) {
			\App\Log\Log::warning(
				'screening rejection mail buffer aborted: missing sender (templateId=' . $templateId . ')',
				'Mail'
			);
			return [];
		}

		if (!\App\Modules\Mail\Models\Module::userCanSendTemplate($userId, $template)) {
			\App\Log\Log::warning(
				'screening rejection mail buffer aborted: invalid sender (templateId='
				. $templateId . ', senderRef=' . $senderRef . ')',
				'Mail'
			);
			return [];
		}

		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($candidateId, self::CANDIDATES_MODULE);
		$textParser = \App\TextParser\TextParser::getInstanceByModel($recordModel);
		$textParser->setParams([
			'template' => $templateId,
			'moduleName' => self::CANDIDATES_MODULE,
			'recordId' => $candidateId,
			'to' => $recipient['email'],
			'sourceModule' => self::PROJECT_MODULE,
			'sourceRecord' => $projectId,
		]);
		$textParser->setSourceRecord($projectId, self::PROJECT_MODULE);

		$parsedSubject = $textParser->setContent((string) ($template['subject'] ?? ''))->parse()->getContent();
		$parsedContent = $textParser->setContent((string) ($template['content'] ?? ''))->parse()->getContent();
		$parsedContent = \App\Email\Mail::appendParsedFooter($parsedContent, $template, $textParser);
		unset($textParser);

		if ($parsedContent === '') {
			return [];
		}

		$smtpId = \App\Email\Mail::resolveTemplateSmtpId($template) ?: \App\Email\Mail::getDefaultSmtp();
		$mailerContent = [
			'smtp_id' => $smtpId,
			'to' => [$recipient['email']],
			'subject' => $parsedSubject,
			'content' => $parsedContent,
			'params' => ['sender_ref' => $senderRef],
		];

		try {
			Buffer::enqueueFromMailerContent(
				$projectId,
				$candidateId,
				DelayedEmailType::STATUS_CHANGE,
				\App\Email\Mailer::withSmtpSenderRef($mailerContent)
			);
		} catch (\App\Exceptions\AppException $e) {
			\App\Log\Log::warning('screening rejection mail buffer enqueue failed: ' . $e->getMessage(), 'Mail');
			return [];
		}

		return ['delayedMail' => ['enqueued' => true]];
	}
}
