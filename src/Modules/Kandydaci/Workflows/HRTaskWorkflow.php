<?php

namespace App\Modules\Kandydaci\Workflows;

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
     * @param \App\Modules\Base\Models\Record $recordModel
     */
    public static function addCommentToConsultantThatMailWasSent(\App\Modules\Base\Models\Record $recordModel) {

        if (($relId = $recordModel->get('related_to')) && 'Konsultanci' === \App\Record::getType($relId)) {

            $commentForProject = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
            $commentForProject->set('commentcontent',  App\Language::translate('PLL_MAIL_SENT_STUDY', $recordModel->getModuleName()));
            $commentForProject->set('related_to', $relId);
            $commentForProject->set('assigned_user_id', (int) (\App\User\CurrentUser::getId() ?? 0));
            $commentForProject->save();
        }
    }
}
