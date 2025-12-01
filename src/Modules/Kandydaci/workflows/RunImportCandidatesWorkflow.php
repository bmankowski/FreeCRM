<?php

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
     * @param Kandydaci_Record_Model $recordModel
     */
    public static function runImportCandidates(Kandydaci_Record_Model $recordModel) {
		Kandydaci_ScheduledImport_Cron::importNewCandidates();

    }
}
