<?php

namespace App\Modules\Candidates\Workflows;

/**
 * HelpDeskWorkflow.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 */
class RunImportCandidatesWorkflow {

    /**
     * Function to send mail to contacts. Function invoke by workflow.
     *
     * @param \App\Modules\Candidates\Models\Record $recordModel
     */
    public static function runImportCandidates(\App\Modules\Candidates\Models\Record $recordModel) {
		(new \App\Modules\RecruitmentApplication\Services\RecruitmentApplicationImporter())
			->importCandidatesFromApplications();

    }
}
