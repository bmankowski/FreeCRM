<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\RecruitmentApplication\Services\CvImport;

final class ImportErrorMailer
{
	/** @var list<array{dto: ?CvApplicationDto, exception: \Throwable}> */
	private static array $errors = [];

	public static function record(?CvApplicationDto $dto, \Throwable $e): void
	{
		self::$errors[] = ['dto' => $dto, 'exception' => $e];
	}

	public static function sendSummaryIfAny(): void
	{
		if (self::$errors === []) {
			return;
		}

		$count = count(self::$errors);
		$subject = 'FreeCRM: Problem importing RecruitmentApplication'
			. ($count === 1 ? '' : ' (' . $count . ' failures)');

		$content = '<p>CV import finished with ' . $count . ' error(s):</p>';
		foreach (self::$errors as $index => $entry) {
			$dto = $entry['dto'];
			$e = $entry['exception'];
			$content .= '<hr>';
			$content .= '<p><strong>Error ' . ($index + 1) . '</strong></p>';
			if ($dto !== null && $dto->applicationNumber !== '') {
				$content .= '<p>Application no: ' . htmlspecialchars($dto->applicationNumber) . '</p>';
			}
			if ($dto !== null && $dto->jsonFilePath !== '') {
				$content .= '<p>JSON file: ' . htmlspecialchars($dto->jsonFilePath) . '</p>';
			}
			if ($dto !== null && $dto->cvAttachmentPath !== '') {
				$content .= '<p>CV path: ' . htmlspecialchars($dto->cvAttachmentPath) . '</p>';
			}
			$content .= '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
		}

		\App\Email\Mailer::addMail([
			'to' => ['bmankowski@gmail.com' => 'Bartłomiej Mańkowski'],
			'subject' => $subject,
			'content' => $content,
			'smtp_id' => 2,
		]);

		self::$errors = [];
	}
}
