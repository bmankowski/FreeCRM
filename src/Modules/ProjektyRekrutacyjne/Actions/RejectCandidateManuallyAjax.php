<?php

namespace App\Modules\ProjektyRekrutacyjne\Actions;

use App\Exceptions\IllegalValue;

/* +***********************************************************************************
 * Reject candidate manually action model class.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Adrian Koń <a.kon@yetiforce.com>
 * *********************************************************************************** */

class RejectCandidateManuallyAjax extends \App\Base\Controllers\BaseActionController
{
    private const REJECTION_REASONS = [
        'NO_EXPERIENCE' => 'PLL_REJECTION_REASON_NO_EXPERIENCE',
        'MISSING_SKILLS' => 'PLL_REJECTION_REASON_MISSING_SKILLS',
        'PROFILE_FIT' => 'PLL_REJECTION_REASON_PROFILE_FIT',
        'MISSING_POLISH_LANGUAGE' => 'PLL_REJECTION_REASON_MISSING_POLISH_LANGUAGE',
    ];

    /**
     * Check permission.
     *
     * @param \App\Http\Vtiger_Request $request
     */
    public function checkPermission(\App\Http\Vtiger_Request $request)
    {
    }

    /**
     * Process.
     *
     * @param \App\Http\Vtiger_Request $request
     */
    public function process(\App\Http\Vtiger_Request $request)
    {
        try {
            $candidateId = $request->getInteger('candidateId');
            $projectId = $request->getInteger('projectId');
            $rejectionReason = $request->getByType('rejectionReason', 'Alnum');
        } catch (IllegalValue $e) {
            $response = new \App\Http\Vtiger_Response();
            $response->setResult([
                'success' => false,
                'message' => "PLL_REJECT_FAILED"
            ]);
            $response->emit();
            return;
        }
        $candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId);
        $project = \App\Modules\Base\Models\Record::getInstanceById($projectId);
        /* @var \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers $typeRelationModel */
        $typeRelationModel = \App\Modules\Base\Models\Relation::getInstance($project->getModule(), $candidate->getModule())->getTypeRelationModel();

        $relationData = $typeRelationModel->getRelationData($projectId, $candidateId);
        $sourceStatus = $relationData['recruitment_status_rel'];
        $result = $typeRelationModel->changeStatus($projectId, $candidateId, $sourceStatus, 'PPL_REJECTED_AFTER_CV');
        if ($result && isset(self::REJECTION_REASONS[$rejectionReason])) {
            $typeRelationModel->updateRelationData($projectId, $candidateId, [
                'comment_rel' => $this->buildRejectionReasonComment($relationData, $rejectionReason),
            ]);
        }
        $response = new \App\Http\Vtiger_Response();
        $response->setResult([
            'success' => (bool)$result,
            'message' => $result ? "PLL_REJECT_SUCCESS" : "PLL_REJECT_FAILED"
        ]);
        $response->emit();
    }

    private function buildRejectionReasonComment(array $relationData, string $rejectionReason): string
    {
        $reasonLabel = \App\Language::translate(self::REJECTION_REASONS[$rejectionReason], 'ProjektyRekrutacyjne');
        $prefix = \App\Language::translate('PLL_REJECTION_REASON_COMMENT_PREFIX', 'ProjektyRekrutacyjne');
        $reasonComment = $prefix . ': ' . $reasonLabel;
        $currentComment = trim((string)($relationData['comment_rel'] ?? ''));
        if ('' === $currentComment) {
            return $reasonComment;
        }
        if (false !== strpos($currentComment, $reasonComment)) {
            return $currentComment;
        }
        return $currentComment . "\n" . $reasonComment;
    }
}
