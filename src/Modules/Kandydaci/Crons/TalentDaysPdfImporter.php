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

namespace App\Modules\Kandydaci\Crons;

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
					$candidate = \App\Modules\Kandydaci\Models\Record::getInstanceById($candidateId, 'Kandydaci');
					$comment = \App\Modules\Base\Models\Record::getCleanInstance('ModComments');
					$comment->set('assigned_user_id', $automatUser->getId());
					$comment->set('related_to', $candidate->getId());
					$comment->set('commentcontent', 'Kandydat zaaplikował ponownie podczas TalentDays');
					$comment->save();
				} else {
					$candidate = \App\Modules\Base\Models\Record::getCleanInstance('Kandydaci');
					$candidate->set('name', $fullName);
					$candidate->set('status_kandydata', 'Kandydat');
					$candidate->set('email_prywatny', $email);
					$candidate->set('zrodlo_aplikacji', 'TalentDays');
					$candidate->set('is_future_contact_allowed', 1);
					$candidate->set('data_maksymalny_kontakt_rodo', date('Y-m-d', strtotime('+3 years')));
					$candidate->save();
					$candidate = \App\Modules\Kandydaci\Models\Record::getInstanceById($candidate->getId(), 'Kandydaci');
				}
				$candidate->set('application_id', $candidateData['applicationNumber']);
				$cvContent = trim(preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $pdfContent));
				$candidate->set('tresc_cv', $cvContent);
				$relations = DocumentHelper::prepareRelationsString('Kandydaci', (int) $candidate->getId());
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
