<?php

/**
 * HelpDeskWorkflow.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 */
class HRTaskWorkflow {

    /**
     * Function to send mail to contacts. Function invoke by workflow.
     *
     * @param Vtiger_Record_Model $recordModel
     */
    public static function addCommentToConsultantThatMailWasSent(Vtiger_Record_Model $recordModel) {

        if (($relId = $recordModel->get('related_to')) && 'Konsultanci' === \App\Record::getType($relId)) {

            $commentForProject = Vtiger_Record_Model::getCleanInstance("ModComments");
            $commentForProject->set('commentcontent',  App\Language::translate('PLL_MAIL_SENT_STUDY', $recordModel->getModuleName()));
            $commentForProject->set('related_to', $relId);
            $commentForProject->set('assigned_user_id',  \App\User::getCurrentUserId());
            $commentForProject->save();
        }
    }
}
