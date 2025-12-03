<?php

namespace App\Modules\ProjektyRekrutacyjne\Actions;

use App\Exceptions\IllegalValue;

/* +***********************************************************************************
 * Accept candidate manually action model class.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Adrian Koń <a.kon@yetiforce.com>
 * *********************************************************************************** */

class AcceptCandidateManuallyAjax extends \App\Controller\Action
{
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
        } catch (IllegalValue $e) {
            $response = new \App\Http\Vtiger_Response();
            $response->setResult([
                'success' => false,
                'message' => "PLL_ACCEPTANCE_FAILED"
            ]);
            $response->emit();
            return;
        }
        $candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId);
        $project = \App\Modules\Base\Models\Record::getInstanceById($projectId);
        $typeRelationModel = \App\Modules\Base\Models\Relation::getInstance($project->getModule(), $candidate->getModule())->getTypeRelationModel();

        /* @var \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers $typeRelationModel */
        $typeRelationModel = \App\Modules\Base\Models\Relation::getInstance($project->getModule(), $candidate->getModule())->getTypeRelationModel();

        $sourceStatus = $typeRelationModel->getRelationData($projectId, $candidateId)['recruitment_status_rel'];
        $result = $typeRelationModel->changeStatus($projectId, $candidateId, $sourceStatus, 'PPL_CANDIDATE_PASSED_SCREENING');

        $response = new \App\Http\Vtiger_Response();
        $response->setResult([
            'success' => true,
            'message' => "PLL_ACCEPTANCE_SUCCESS"
        ]);
        $response->emit();
    }
}
