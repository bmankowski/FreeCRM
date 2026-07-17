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

namespace App\Modules\ProjektyRekrutacyjne\Services\Verama;

final class VeramaJobFieldMapper
{
	public const JOB_SOURCE = 'verama';
	public const SOURCE_PICKLIST = 'Verama www';
	public const RODZAJ = 'Dla klienta';
	public const ETAP_OPEN = 'Na potrzeby RFI';
	public const ETAP_CLOSED = 'Sprzedaż utracona';
	/** Accounts.accountname used as fixed kontrahent for all Verama jobs. */
	public const KONTRAHENT_ACCOUNT_NAME = 'Ework Verama';
	/** Contacts name used as fixed contact_person for all Verama jobs. */
	public const CONTACT_PERSON_FIRST_NAME = 'Wojciech';
	public const CONTACT_PERSON_LAST_NAME = 'Górski';

	/**
	 * Fields applied on create and on every update.
	 *
	 * @return array<string, mixed>
	 */
	public static function mapUpdatableFields(VeramaJobImportDto $dto): array
	{
		$api = $dto->api;
		$title = trim((string) ($api['title'] ?? ''));
		if ($title === '' && $dto->status === 'CLOSED') {
			$title = 'Verama ' . $dto->externalId;
		}

		$locations = self::formatLocations($api['locations'] ?? null);
		[$needed, $nice] = self::formatSkills($api['skills'] ?? null);
		[$duties, $requirements, $offer] = self::mapSections($dto->descriptionSections);

		$fields = [
			'nazwa_projektu' => $title,
			'etap_sprzedazy' => $dto->status === 'CLOSED' ? self::ETAP_CLOSED : self::ETAP_OPEN,
			'rodzaj' => self::RODZAJ,
			'zrodlo_pozyskania_projektu' => self::SOURCE_PICKLIST,
			'job_source' => self::JOB_SOURCE,
			'external_job_id' => $dto->externalId,
			'reference_no' => self::truncate((string) ($dto->systemId ?? ''), 100),
			'kontrahent' => self::resolveKontrahentAccountId(),
			'contact_person' => self::resolveContactPersonId(),
			'tresc' => $dto->descriptionHtml,
			'your_duties' => $duties,
			'our_requirements' => $requirements,
			'we_offer' => $offer,
			'needed_skills' => self::truncate($needed, 255),
			'nice_to_have_skills' => self::truncate($nice, 255),
			'miejsce_pracy' => self::truncate($locations, 100),
			'workplace_for_map' => self::truncate($locations, 100),
			'remuneration' => self::truncate(self::formatRemuneration($api['rate'] ?? null), 100),
			'oczekiwana_data_zakonczenia' => self::formatDate($api['endDate'] ?? null),
			'ilosc_wakatow' => self::formatPositions($api['numberOfPositions'] ?? null),
			'informacje_dodatkowe' => self::formatAdditionalInfo($dto),
		];

		if (array_key_exists('remoteness', $api) && $api['remoteness'] !== null && $api['remoteness'] !== '') {
			$fields['tryb_pracy'] = self::mapRemoteness($api['remoteness']);
		} elseif ($dto->status === 'OPEN') {
			throw new \RuntimeException('Missing api.remoteness for OPEN job ' . $dto->externalId);
		}

		return $fields;
	}

	public static function resolveKontrahentAccountId(): int
	{
		static $cachedId = null;
		if ($cachedId !== null) {
			return $cachedId;
		}

		$id = (new \App\Db\Query())
			->select(['a.accountid'])
			->from(['a' => 'vtiger_account'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = a.accountid')
			->where([
				'e.deleted' => 0,
				'a.accountname' => self::KONTRAHENT_ACCOUNT_NAME,
			])
			->scalar();

		if ($id === false || $id === null) {
			throw new \RuntimeException(
				'Accounts record not found for Verama kontrahent: ' . self::KONTRAHENT_ACCOUNT_NAME
			);
		}

		$cachedId = (int) $id;

		return $cachedId;
	}

	public static function resolveContactPersonId(): int
	{
		static $cachedId = null;
		if ($cachedId !== null) {
			return $cachedId;
		}

		$id = (new \App\Db\Query())
			->select(['c.contactid'])
			->from(['c' => 'vtiger_contactdetails'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = c.contactid')
			->where([
				'e.deleted' => 0,
				'c.firstname' => self::CONTACT_PERSON_FIRST_NAME,
				'c.lastname' => self::CONTACT_PERSON_LAST_NAME,
			])
			->scalar();

		if ($id === false || $id === null) {
			throw new \RuntimeException(
				'Contacts record not found for Verama contact_person: '
				. self::CONTACT_PERSON_FIRST_NAME . ' ' . self::CONTACT_PERSON_LAST_NAME
			);
		}

		$cachedId = (int) $id;

		return $cachedId;
	}

	public static function mapRemoteness(mixed $remoteness): string
	{
		if ($remoteness === null || $remoteness === '') {
			throw new \RuntimeException('Missing api.remoteness');
		}
		if (!is_numeric($remoteness)) {
			throw new \RuntimeException('Invalid api.remoteness: ' . (string) $remoteness);
		}
		$value = (float) $remoteness;
		if ($value <= 0.0) {
			return 'praca na miejscu';
		}
		if ($value >= 100.0) {
			return 'praca zdalna';
		}

		return 'praca hybrydowa';
	}

	/**
	 * @param array<string, string> $sections
	 * @return array{0: string, 1: string, 2: string}
	 */
	private static function mapSections(array $sections): array
	{
		$dutiesParts = [];
		$reqParts = [];
		$offerParts = [];

		foreach ($sections as $key => $text) {
			$normalized = strtolower($key);
			if (in_array($normalized, ['responsibilities', 'description', 'about', 'duties', 'role'], true)
				|| str_contains($normalized, 'responsib')
				|| str_contains($normalized, 'duties')) {
				$dutiesParts[] = $text;
				continue;
			}
			if (in_array($normalized, ['requirements', 'requirement'], true)
				|| str_contains($normalized, 'require')
				|| str_contains($normalized, 'ideal_candidate')
				|| str_contains($normalized, 'value')
				|| str_contains($normalized, 'succeed')) {
				$reqParts[] = $text;
				continue;
			}
			if (in_array($normalized, ['offer', 'we_offer', 'benefits'], true)
				|| str_contains($normalized, 'offer')) {
				$offerParts[] = $text;
			}
		}

		return [
			implode("\n\n", $dutiesParts),
			implode("\n\n", $reqParts),
			implode("\n\n", $offerParts),
		];
	}

	/**
	 * @return array{0: string, 1: string}
	 */
	private static function formatSkills(mixed $skills): array
	{
		$needed = [];
		$nice = [];
		if (!is_array($skills)) {
			return ['', ''];
		}
		foreach ($skills as $row) {
			if (!is_array($row)) {
				continue;
			}
			$skill = $row['skill'] ?? null;
			$name = '';
			if (is_array($skill)) {
				$name = trim((string) ($skill['name'] ?? ''));
			} elseif (is_string($skill)) {
				$name = trim($skill);
			}
			if ($name === '') {
				continue;
			}
			$priority = strtoupper((string) ($row['priority'] ?? ''));
			if ($priority === 'PREFERRED') {
				$nice[] = $name;
			} else {
				$needed[] = $name;
			}
		}

		return [implode(', ', $needed), implode(', ', $nice)];
	}

	private static function formatLocations(mixed $locations): string
	{
		if (!is_array($locations)) {
			return '';
		}
		$cities = [];
		foreach ($locations as $loc) {
			if (!is_array($loc)) {
				continue;
			}
			$city = trim((string) ($loc['city'] ?? ''));
			if ($city !== '' && !in_array($city, $cities, true)) {
				$cities[] = $city;
			}
		}

		return implode(', ', $cities);
	}

	private static function formatRemuneration(mixed $rate): string
	{
		if (!is_array($rate)) {
			return '';
		}
		$max = $rate['maxRate'] ?? null;
		if ($max === null || $max === '') {
			return '';
		}
		$currency = trim((string) ($rate['currency'] ?? ''));
		$type = strtoupper((string) ($rate['clientRateType'] ?? ''));
		$unit = $type === 'HOURLY' ? 'h' : ($type !== '' ? strtolower($type) : '');
		$amount = is_numeric($max) ? rtrim(rtrim(number_format((float) $max, 2, '.', ''), '0'), '.') : (string) $max;
		$parts = [$amount];
		if ($currency !== '') {
			$parts[] = $currency;
		}
		$text = implode(' ', $parts);
		if ($unit !== '') {
			$text .= ' / ' . $unit;
		}

		return $text;
	}

	private static function formatDate(mixed $date): string
	{
		if ($date === null || $date === '') {
			return '';
		}
		$raw = (string) $date;
		if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
			return $m[1];
		}

		return '';
	}

	private static function formatPositions(mixed $positions): string
	{
		if ($positions === null || $positions === '') {
			return '';
		}

		return (string) (int) $positions;
	}

	private static function truncate(string $value, int $maxLen): string
	{
		if ($maxLen <= 0 || mb_strlen($value) <= $maxLen) {
			return $value;
		}

		return mb_substr($value, 0, $maxLen);
	}

	private static function formatAdditionalInfo(VeramaJobImportDto $dto): string
	{
		$client = '';
		$admin = '';
		$legal = $dto->api['legalEntityClient'] ?? null;
		if (is_array($legal)) {
			$client = trim((string) ($legal['name'] ?? ''));
		}
		$adminEntity = $dto->api['administratorLegalEntityClient'] ?? null;
		if (is_array($adminEntity)) {
			$admin = trim((string) ($adminEntity['name'] ?? ''));
		}

		$lines = [
			'Źródło: Verama',
			'URL: ' . $dto->url,
		];
		if ($dto->systemId !== null) {
			$lines[] = 'systemId: ' . $dto->systemId;
		}
		if ($client !== '') {
			$lines[] = 'Klient: ' . $client;
		}
		if ($admin !== '') {
			$lines[] = 'Administrator: ' . $admin;
		}
		if ($dto->scrapedAt !== null && $dto->scrapedAt !== '') {
			$lines[] = 'scraped_at: ' . $dto->scrapedAt;
		}

		return implode("\n", $lines);
	}
}
