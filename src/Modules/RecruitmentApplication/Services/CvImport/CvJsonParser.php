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

final class CvJsonParser
{
	public static function parseFile(string $pendingDirectory, string $jsonFilePath, string $applicationNumber): CvApplicationDto
	{
		$rawJson = file_get_contents($jsonFilePath);
		if ($rawJson === false) {
			throw new \RuntimeException("Cannot read JSON file: {$jsonFilePath}");
		}
		$data = json_decode($rawJson, true);
		if (!is_array($data)) {
			throw new \RuntimeException("Invalid JSON in file: {$jsonFilePath}");
		}
		if (isset($data['entries']) && is_array($data['entries'])) {
			return self::parseMetForm($pendingDirectory, $jsonFilePath, $applicationNumber, $rawJson, $data);
		}
		return self::parseJetForm($pendingDirectory, $jsonFilePath, $applicationNumber, $rawJson, $data);
	}

	private static function parseJetForm(
		string $pendingDirectory,
		string $jsonFilePath,
		string $applicationNumber,
		string $rawJson,
		array $data
	): CvApplicationDto {
		$dto = new CvApplicationDto();
		$dto->isMetForm = false;
		$dto->applicationNumber = $applicationNumber;
		$dto->rawJsonData = $rawJson;
		$dto->jsonFilePath = $jsonFilePath;
		$dto->pendingDirectory = $pendingDirectory;
		$dto->candidateName = (string) ($data['full_name'] ?? $data['name'] ?? '');
		$dto->candidateEmail = filter_var((string) ($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
		$dto->candidateOriginalPhone = (string) ($data['phone_number'] ?? '');
		$dto->candidateTransformedPhone = PhoneNormalizer::normalize($dto->candidateOriginalPhone);
		$cvSaved = (string) ($data['cv_saved_filename'] ?? '');
		$dto->cvAttachmentPath = $cvSaved !== '' ? $pendingDirectory . $cvSaved : '';
		$dto->projectId = (string) ($data['project_id'] ?? '');
		$dto->sourceId = (string) ($data['sourceId'] ?? $data['cv-source-id'] ?? '');
		$dto->agreeToContact = (string) ($data['future_recruitment_consent'] ?? '');
		$dto->originalFilename = (string) ($data['cv_original_filename'] ?? '');
		$dto->availability = (string) ($data['available_from'] ?? '');
		$dto->financialExpectations = (string) ($data['expected_salary'] ?? '');
		$dto->message = (string) ($data['message'] ?? '');
		$dto->preferredContractType = (string) ($data['preferred_contract_type'] ?? '');
		$dto->expectedSalary = $dto->financialExpectations;
		$dto->jobTitle = (string) ($data['job_title'] ?? '');
		$dto->formType = (string) ($data['formtype'] ?? '');
		$dto->submittedAt = (string) ($data['created_at'] ?? '');
		$dto->postId = (string) ($data['post_id'] ?? '');
		$dto->formId = (string) ($data['__form_id'] ?? '');
		$dto->referrerUrl = (string) ($data['__refer'] ?? '');
		$dto->formLanguage = (string) ($data['cv-jezyk-formularza'] ?? '');
		$dto->cvOriginalFilename = $dto->originalFilename;
		$dto->cvSavedFilename = $cvSaved;
		$dto->cvAttachmentUrl = (string) ($data['attachment_cv'] ?? '');
		self::applyReferralJetForm($dto, $data);
		return $dto;
	}

	private static function applyReferralJetForm(CvApplicationDto $dto, array $data): void
	{
		if (!empty($data['cv-imie-nazwisko-polecajaca'])) {
			$dto->isReferredByEmployee = true;
			$dto->referredByEmployee = filter_var((string) $data['cv-imie-nazwisko-polecajaca'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$dto->referredOnPosition = filter_var((string) ($data['cv-nazwa-stanowiska'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$dto->referredByEmail = filter_var((string) ($data['cv-email-polecajaca'] ?? ''), FILTER_SANITIZE_EMAIL);
			return;
		}
		$dto->isReferredByEmployee = false;
		$dto->referredByEmployee = '';
		$dto->referredOnPosition = '';
		$dto->referredByEmail = '';
	}

	private static function parseMetForm(
		string $pendingDirectory,
		string $jsonFilePath,
		string $applicationNumber,
		string $rawJson,
		array $data
	): CvApplicationDto {
		$entries = $data['entries'] ?? [];
		if (!is_array($entries)) {
			throw new \RuntimeException('No name in application');
		}
		$plName = trim((string) ($entries['cv-imie-nazwisko'] ?? ''));
		$enName = trim((string) ($entries['cv-imie-nazwisko-en'] ?? ''));
		if ($plName === '' && $enName === '') {
			throw new \RuntimeException('No name in application');
		}

		$dto = new CvApplicationDto();
		$dto->isMetForm = true;
		$dto->applicationNumber = $applicationNumber;
		$dto->rawJsonData = $rawJson;
		$dto->jsonFilePath = $jsonFilePath;
		$dto->pendingDirectory = $pendingDirectory;
		$dto->submittedAt = (string) ($data['created_at'] ?? '');

		if ($plName !== '') {
			$dto->candidateName = self::formatMetFormName($plName);
			$dto->candidateEmail = filter_var((string) ($entries['cv-email'] ?? ''), FILTER_SANITIZE_EMAIL);
			$dto->availability = filter_var((string) ($entries['cv-od-kiedy'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$dto->financialExpectations = filter_var((string) ($entries['cv-oczekiwania'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$dto->message = filter_var((string) ($entries['cv-zostaw-wiadomosc'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			if ($dto->message === '') {
				$dto->message = filter_var((string) ($entries['cv-wiadomosc'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			}
			$uploadKey = 'cv-zalacz-cv';
			$dto->formLanguage = 'pl';
		} else {
			$dto->candidateName = self::switchFirstAndLastName(
				html_entity_decode(filter_var($enName, FILTER_SANITIZE_FULL_SPECIAL_CHARS), ENT_QUOTES | ENT_HTML401, 'UTF-8')
			);
			$dto->candidateEmail = filter_var((string) ($entries['cv-email-en'] ?? ''), FILTER_SANITIZE_EMAIL);
			$dto->availability = filter_var((string) ($entries['cv-od-kiedy-en'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$dto->financialExpectations = filter_var((string) ($entries['cv-oczekiwania-en'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$dto->message = filter_var((string) ($entries['cv-zostaw-wiadomosc-en'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			if ($dto->message === '') {
				$dto->message = filter_var((string) ($entries['cv-wiadomosc-en'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			}
			$uploadKey = 'cv-zalacz-cv-en';
			$dto->formLanguage = 'en';
		}

		$dto->projectId = filter_var((string) ($entries['cv-id-projektu'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$dto->sourceId = filter_var((string) ($entries['cv-source-id'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$dto->agreeToContact = filter_var((string) ($entries['cv-zgoda'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$dto->expectedSalary = $dto->financialExpectations;
		$dto->jobTitle = filter_var((string) ($entries['cv-nazwa-stanowiska'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$dto->formType = '';

		$uploads = $data['file_uploads'][$uploadKey][0] ?? [];
		$dto->originalFilename = filter_var((string) ($uploads['name'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$basename = filter_var(basename((string) ($uploads['file'] ?? '')), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		if ($basename === '') {
			$basename = filter_var(basename((string) ($uploads['url'] ?? '')), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		}
		if ($dto->originalFilename === '') {
			$dto->originalFilename = $basename;
		}
		$dto->cvSavedFilename = $basename;
		$dto->cvOriginalFilename = $dto->originalFilename;
		$dto->cvAttachmentUrl = (string) ($uploads['url'] ?? '');
		$dto->cvAttachmentPath = $basename !== '' ? $pendingDirectory . $basename : '';

		$dto->candidateOriginalPhone = filter_var(
			(string) ($entries['cv-numer-phoneu'] ?? $entries['numer-phoneu'] ?? $entries['cv-numer-phoneu-en'] ?? $entries['numer-phoneu-en'] ?? ''),
			FILTER_SANITIZE_NUMBER_INT
		);
		$dto->candidateTransformedPhone = PhoneNormalizer::normalize($dto->candidateOriginalPhone);
		if ($dto->candidateTransformedPhone !== '' && !PhoneNormalizer::isValidE164($dto->candidateTransformedPhone)) {
			CvImportLogger::log('Invalid phone number for candidate');
			$dto->candidateTransformedPhone = '';
		}

		self::applyReferralMetForm($dto, $entries);
		return $dto;
	}

	private static function formatMetFormName(string $name): string
	{
		$decoded = html_entity_decode(filter_var($name, FILTER_SANITIZE_FULL_SPECIAL_CHARS), ENT_QUOTES | ENT_HTML401, 'UTF-8');
		$swapped = self::switchFirstAndLastName($decoded);
		return self::formatFullName($swapped) ?? $swapped;
	}

	private static function switchFirstAndLastName(string $name): string
	{
		$parts = explode(' ', trim($name));
		if (count($parts) < 2) {
			return $name;
		}
		return implode(' ', array_reverse($parts));
	}

	private static function formatFullName(string $fullName): ?string
	{
		if (!preg_match('/^[A-Z]+\s[A-Z]+$/', trim($fullName))) {
			return $fullName;
		}
		return ucwords(strtolower($fullName));
	}

	private static function applyReferralMetForm(CvApplicationDto $dto, array $entries): void
	{
		if (!empty($entries['cv-imie-nazwisko-polecajaca'])) {
			$dto->isReferredByEmployee = true;
			$dto->referredByEmployee = filter_var((string) $entries['cv-imie-nazwisko-polecajaca'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$dto->referredOnPosition = filter_var((string) ($entries['cv-nazwa-stanowiska'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$dto->referredByEmail = filter_var((string) ($entries['cv-email-polecajaca'] ?? ''), FILTER_SANITIZE_EMAIL);
			return;
		}
		$dto->isReferredByEmployee = false;
		$dto->referredByEmployee = '';
		$dto->referredOnPosition = '';
		$dto->referredByEmail = '';
	}
}
