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

/**
 * Auto-sends application-received confirmation when a candidate is linked as PPL_APPLIED.
 * From address comes from the template's system SMTP (rekrutacja@), not a user mailbox.
 */
class ApplicationReceivedMail
{
	public const SYS_NAME = 'kandydaci_potwierdzenie_otrzymania_aplikacji';

	public const PROJECT_MODULE = 'ProjektyRekrutacyjne';

	/**
	 * @return bool true when a mail was enqueued to the outbound queue
	 */
	public static function sendForAppliedLink(int $candidateId, int $projectId): bool
	{
		if ($candidateId <= 0 || $projectId <= 0) {
			return false;
		}

		if (!\App\Core\AppConfig::main('isActiveSendingMails')) {
			return false;
		}

		if (!\App\Modules\Candidates\Models\RelatedListLeftSideEmail::recordHasEmail($candidateId)) {
			\App\Log\Log::warning(
				'application received mail skipped: candidate has no email (candidateId=' . $candidateId . ')',
				'Mail'
			);

			return false;
		}

		try {
			$project = \App\Modules\Base\Models\Record::getInstanceById($projectId, self::PROJECT_MODULE);
		} catch (\Throwable $e) {
			\App\Log\Log::warning(
				'application received mail skipped: project load failed (projectId=' . $projectId . '): ' . $e->getMessage(),
				'Mail'
			);

			return false;
		}

		$accountId = (int) $project->get('kontrahent');
		$templateIds = \App\Modules\EmailTemplates\Models\RecruitmentTemplate::resolveShortNamesForAccount(
			[self::SYS_NAME],
			$accountId
		);
		if ($templateIds === []) {
			\App\Log\Log::warning(
				'application received mail skipped: template not found (sys_name=' . self::SYS_NAME . ')',
				'Mail'
			);

			return false;
		}

		$templateId = (int) $templateIds[0];
		$template = \App\Email\Mail::getTemplete($templateId) ?: [];
		if ($template === []) {
			\App\Log\Log::warning(
				'application received mail skipped: template row empty (templateId=' . $templateId . ')',
				'Mail'
			);

			return false;
		}

		if (\App\Modules\Mail\Models\Module::resolveSenderType($template) !== 'system_smtp') {
			\App\Log\Log::error(
				'application received mail aborted: template must use system_smtp (sys_name='
				. self::SYS_NAME . ', templateId=' . $templateId . ')',
				'Mail'
			);

			return false;
		}

		$userId = self::resolveContextUserId();
		if ($userId <= 0 || !\App\Modules\Mail\Models\Module::userCanSendTemplate($userId, $template)) {
			\App\Log\Log::warning(
				'application received mail skipped: system SMTP unavailable (templateId=' . $templateId . ')',
				'Mail'
			);

			return false;
		}

		$result = RecruitmentStatusTransitionMail::sendAutoTemplates(
			$candidateId,
			$projectId,
			[
				[
					'templateId' => $templateId,
					'shortName' => self::SYS_NAME,
				],
			],
			$userId
		);

		if ($result['sent'] > 0) {
			return true;
		}

		\App\Log\Log::warning(
			'application received mail failed (candidateId=' . $candidateId
			. ', projectId=' . $projectId
			. ', templateId=' . $templateId . ')',
			'Mail'
		);

		return false;
	}

	/** Context user for Outbound audit only — From is template smtp_id (rekrutacja@). */
	private static function resolveContextUserId(): int
	{
		$currentId = (int) (\App\User\CurrentUser::getId() ?? 0);
		if ($currentId > 0) {
			return $currentId;
		}
		$automatId = (int) (\App\Modules\Users\Models\Record::getUserIdByName('automat') ?? 0);
		if ($automatId > 0) {
			return $automatId;
		}

		return 1;
	}
}
