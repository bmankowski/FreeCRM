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

final class CvApplicationDto
{
	public string $applicationNumber;
	public string $rawJsonData;
	public string $jsonFilePath;
	public string $pendingDirectory;
	public string $candidateName;
	public string $candidateEmail;
	public string $candidateOriginalPhone;
	public string $candidateTransformedPhone;
	public string $cvAttachmentPath;
	public string $projectId;
	public string $sourceId;
	public string $agreeToContact;
	public string $originalFilename;
	public string $availability;
	public string $financialExpectations;
	public string $message;
	public string $preferredContractType;
	public string $expectedSalary;
	public string $jobTitle;
	public bool $isReferredByEmployee;
	public string $referredByEmployee;
	public string $referredOnPosition;
	public string $referredByEmail;
	public string $formType;
	public string $submittedAt;
	public string $postId;
	public string $formId;
	public string $referrerUrl;
	public string $formLanguage;
	public string $cvOriginalFilename;
	public string $cvSavedFilename;
	public string $cvAttachmentUrl;
	public bool $isMetForm;

	public function toLegacyArray(): array
	{
		return [
			'candidateApplicationNumber' => $this->applicationNumber,
			'rawJSONData' => $this->rawJsonData,
			'jsonFilePath' => $this->jsonFilePath,
			'directory' => $this->pendingDirectory,
			'candidateName' => $this->candidateName,
			'candidateEmail' => $this->candidateEmail,
			'candidateOriginalPhone' => $this->candidateOriginalPhone,
			'candidateTransformedPhone' => $this->candidateTransformedPhone,
			'filename' => $this->cvAttachmentPath,
			'projectId' => $this->projectId,
			'sourceId' => $this->sourceId,
			'agreeToContact' => $this->agreeToContact,
			'originalFilename' => $this->originalFilename,
			'availability' => $this->availability,
			'financialExpectations' => $this->financialExpectations,
			'message' => $this->message,
			'preferredContractType' => $this->preferredContractType,
			'expectedSalary' => $this->expectedSalary,
			'futureRecruitmentConsent' => $this->agreeToContact,
			'jobTitle' => $this->jobTitle,
			'isReferredByEmployee' => $this->isReferredByEmployee,
			'referredByEmployee' => $this->referredByEmployee,
			'referredOnPosition' => $this->referredOnPosition,
			'referredByEmail' => $this->referredByEmail,
		];
	}
}
