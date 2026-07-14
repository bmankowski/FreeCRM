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

namespace App\Modules\Candidates\Crons;

use App\Modules\PrivacyConsent\PrivacyConsentWriter;
use App\Modules\RecruitmentApplication\Services\CvImport\ApplicationImportRepository;
use App\Modules\RecruitmentApplication\Services\CvImport\CandidateApplicationSideEffects;
use App\Modules\RecruitmentApplication\Services\CvImport\CvImportLogger;
use App\Modules\RecruitmentApplication\Services\CvImport\DocumentHelper;

final class TalentDaysPdfImporter
{
	public static function importFromFolder(string $directory): void
	{
		$automatUserId = \App\Modules\Users\Models\Record::getUserIdByName('automat');
		$automatUser = \App\Modules\Users\Models\Record::getInstanceById($automatUserId, 'Users');

		if ($directory === '' || !is_dir($directory)) {
			CvImportLogger::log("Directory does not exist: {$directory}");
			return;
		}
		if (!str_ends_with($directory, '/')) {
			$directory .= '/';
		}

		$filesToProcess = glob($directory . '*.pdf') ?: [];
		CvImportLogger::log('TalentDays import started. Files: ' . count($filesToProcess));
		foreach ($filesToProcess as $filePath) {
			try {
				$fileName = basename($filePath, '.pdf');
				$parts = explode('-', $fileName);
				if (count($parts) < 3) {
					CvImportLogger::log('Invalid filename format: ' . $fileName);
					continue;
				}
				$candidateData = [
					'firstname' => $parts[0],
					'lastname' => $parts[1],
					'applicationNumber' => $parts[2],
				];
				if (ApplicationImportRepository::isApplicationImported($candidateData['applicationNumber'])) {
					CvImportLogger::log('Application already imported: ' . $candidateData['applicationNumber']);
					continue;
				}
				try {
					$pdfContent = substr(\App\Utils\DocumentParser::parseFromFile($filePath), 0, 10000);
				} catch (\Exception $e) {
					CvImportLogger::log('PDF parse error: ' . $e->getMessage());
					$pdfContent = '';
				}
				preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}\b/i', $pdfContent, $matches);
				$email = $matches[0] ?? null;
				if (empty($email)) {
					CvImportLogger::log('No email in file: ' . $filePath);
					continue;
				}
				$fullName = $candidateData['lastname'] . ' ' . $candidateData['firstname'];
				$candidateId = CandidateApplicationSideEffects::getCandidateIdByNameAndEmail($fullName, $email);
				if ($candidateId !== null) {
					$candidate = \App\Modules\Candidates\Models\Record::getInstanceById($candidateId, 'Candidates');
					$comment = \App\Modules\Base\Models\Record::getCleanInstance('ModComments');
					$comment->set('assigned_user_id', $automatUser->getId());
					$comment->set('related_to', $candidate->getId());
					$comment->set('commentcontent', 'Kandydat zaaplikował ponownie podczas TalentDays');
					$comment->save();
				} else {
					$candidate = \App\Modules\Base\Models\Record::getCleanInstance('Candidates');
					$candidate->set('name', $fullName);
					$candidate->set('candidate_status', 'Kandydat');
					$candidate->set('email_private', $email);
					$candidate->set('application_source', 'TalentDays');
					$candidate->save();
					$candidateId = (int) $candidate->getId();
					PrivacyConsentWriter::grant(
						$candidateId,
						'import',
						date('Y-m-d', strtotime('+3 years'))
					);
					$candidate = \App\Modules\Candidates\Models\Record::getInstanceById($candidateId, 'Candidates');
				}
				$candidate->set('application_id', $candidateData['applicationNumber']);
				$candidate->set('cv_text', \App\Modules\Candidates\Services\CvTextNormalizer::fromExtractedDocument($pdfContent));
				$relations = DocumentHelper::prepareRelationsString('Candidates', (int) $candidate->getId());
				$documentRecord = DocumentHelper::saveAndDeleteFile($filePath, 'CV', $relations);
				$candidate->transformDocumentToCV($documentRecord);
				$candidate->save();
			} catch (\Throwable $e) {
				CvImportLogger::log('Error: ' . $e->getMessage());
				\App\Log\Log::error($e);
			}
		}
		CvImportLogger::log('TalentDays import finished');
	}
}
