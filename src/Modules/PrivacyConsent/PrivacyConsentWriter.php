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

namespace App\Modules\PrivacyConsent;

final class PrivacyConsentWriter
{
	private const CONSENT_TYPE = 'future_contact';

	public static function grant(
		int $candidateId,
		string $source,
		string $expiresAt,
		?int $sourceRecordId = null,
		?string $linkActionJti = null,
	): void {
		self::appendEvent($candidateId, 'granted', $source, $expiresAt, $sourceRecordId, $linkActionJti);
		self::syncCandidateCache($candidateId, true, $expiresAt);
	}

	public static function revoke(
		int $candidateId,
		string $source,
		?string $linkActionJti = null,
	): void {
		$today = gmdate('Y-m-d');
		self::appendEvent($candidateId, 'revoked', $source, $today, null, $linkActionJti);
		self::syncCandidateCache($candidateId, false, $today);
	}

	private static function appendEvent(
		int $candidateId,
		string $action,
		string $source,
		string $expiresAt,
		?int $sourceRecordId,
		?string $linkActionJti,
	): void {
		$consent = \App\Modules\Base\Models\Record::getCleanInstance('PrivacyConsent');
		$consent->set('subject', $candidateId);
		$consent->set('consenttype', self::CONSENT_TYPE);
		$consent->set('consentaction', $action);
		$consent->set('source', $source);
		$consent->set('effectiveat', gmdate('Y-m-d'));
		$consent->set('expiresat', $expiresAt);
		$consent->set('label', $action . ' / ' . $source);
		if ($sourceRecordId !== null && $sourceRecordId > 0) {
			$consent->set('sourcerecordid', $sourceRecordId);
		}
		if ($linkActionJti !== null && $linkActionJti !== '') {
			$consent->set('linkactionjti', $linkActionJti);
		}
		$consent->save();
	}

	private static function syncCandidateCache(int $candidateId, bool $allowed, string $maxContactDate): void
	{
		$candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId, 'Candidates');
		$candidate->set('is_future_contact_allowed', $allowed ? 1 : 0);
		$candidate->set('gdpr_max_contact_date', $maxContactDate);
		$candidate->set('mode', 'edit');
		$candidate->save();
	}
}
