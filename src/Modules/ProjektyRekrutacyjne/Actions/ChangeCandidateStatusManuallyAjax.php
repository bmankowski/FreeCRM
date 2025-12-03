<?php

namespace App\Modules\ProjektyRekrutacyjne\Actions;

use App\Exceptions\IllegalValue;

/* +***********************************************************************************
 * Change candidate status manually action model class.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Adrian Koń <a.kon@yetiforce.com>
 * *********************************************************************************** */

class ChangeCandidateStatusManuallyAjax extends \App\Controller\Action
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
            $sourceStatus = $request->getRaw('sourceStatus');
            $destinationStatus = $request->getRaw('destinationStatus');
        } catch (IllegalValue $e) {
            $response = new \App\Http\Vtiger_Response();
            $response->setResult([
                'success' => false,
                'message' => "PLL_ACCEPTANCE_FAILED"
            ]);
            $response->emit();
            \App\Log::error("Error " . $e->getDisplayMessage());
            return;
        }
        if (empty($candidateId) || empty($projectId) || empty($sourceStatus) || empty($destinationStatus)) {
            $response = new \App\Http\Vtiger_Response();
            $response->setResult([
                'success' => false,
                'message' => "PLL_ACCEPTANCE_FAILED" . " candidateId: " . $candidateId . " projectId: " . $projectId . " sourceStatus: " . $sourceStatus . " destinationStatus: " . $destinationStatus
            ]);
            $response->emit();
            \App\Log::error("PLL_ACCEPTANCE_FAILED" . " candidateId: " . $candidateId . " projectId: " . $projectId . " sourceStatus: " . $sourceStatus . " destinationStatus: " . $destinationStatus);
            return;
        }

        try {
            $candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId);
            $project = \App\Modules\Base\Models\Record::getInstanceById($projectId);
            /* @var \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers $typeRelationModel */
            $typeRelationModel = \App\Modules\Base\Models\Relation::getInstance($project->getModule(), $candidate->getModule())->getTypeRelationModel();
        } catch (\Exception $e) {
            $response = new \App\Http\Vtiger_Response();
            $response->setResult([
                'success' => false,
                'message' => "PLL_NO_SUCH_RECORD"
            ]);
            //@todo add translation
            $response->emit();
            \App\Log::error("Error " . $e->getMessage());
            return;
        }

        $result = $typeRelationModel->changeStatus($projectId, $candidateId, $sourceStatus, $destinationStatus);
        $response = new \App\Http\Vtiger_Response();
        $response->setResult([
            'success' => true,
            'message' => "PLL_ACCEPTANCE_SUCCESS"
        ]);
        $response->emit();
    }
}
