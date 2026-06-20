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

class ChangeCandidateStatusManuallyAjax extends \App\Base\Controllers\BaseActionController
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
        if (!$result && \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::isConfigured()
            && !\App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::isAllowed($sourceStatus, $destinationStatus)) {
            $response->setResult([
                'success' => false,
                'message' => 'PLL_STATUS_TRANSITION_NOT_ALLOWED',
            ]);
            $response->emit();
            return;
        }
        $resultPayload = [
            'success' => (bool) $result,
            'message' => $result ? 'PLL_ACCEPTANCE_SUCCESS' : 'PLL_ACCEPTANCE_FAILED',
        ];
        if ($result) {
            $mailActions = $this->buildMailActions(
                $candidateId,
                $projectId,
                $sourceStatus,
                $destinationStatus,
                (int) $request->getUser()->getId()
            );
            if (!empty($mailActions['mailPrompt'])) {
                $resultPayload['mailPrompt'] = $mailActions['mailPrompt'];
            }
            if (!empty($mailActions['autoSend'])) {
                $resultPayload['autoSend'] = $mailActions['autoSend'];
            }
        }
        $response->setResult($resultPayload);
        $response->emit();
    }

    /**
     * @return array{mailPrompt?: array{candidateId: int, projectId: int, templateIds: list<int>}, autoSend?: array{sent: int, failed: int, failedShortNames: list<string>}}
     */
    private function buildMailActions(
        int $candidateId,
        int $projectId,
        string $sourceStatus,
        string $destinationStatus,
        int $userId
    ): array {
        $candidatesModule = \App\Modules\Base\Models\Module::getInstance('Candidates');
        if (!$candidatesModule
            || !$candidatesModule->isPermitted('MassComposeEmail')
            || !\App\Core\AppConfig::main('isActiveSendingMails')
            || !\App\Email\Mail::getDefaultSmtp()) {
            return [];
        }

        $accountId = 0;
        try {
            $project = \App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne');
            $accountId = (int) $project->get('kontrahent');
        } catch (\Throwable) {
            return [];
        }
        if ($accountId <= 0) {
            return [];
        }

        if (!\App\Modules\Candidates\Models\RelatedListLeftSideEmail::recordHasEmail($candidateId)) {
            return [];
        }

        $actions = \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransitionMail::resolveMailActions(
            $sourceStatus,
            $destinationStatus,
            $accountId
        );
        if ($actions === []) {
            return [];
        }

        $autoItems = [];
        $promptTemplateIds = [];
        foreach ($actions as $action) {
            if (($action['deliveryMode'] ?? '') === \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransitionMail::DELIVERY_AUTO) {
                $autoItems[] = [
                    'templateId' => (int) $action['templateId'],
                    'shortName' => (string) $action['shortName'],
                ];
            } else {
                $promptTemplateIds[] = (int) $action['templateId'];
            }
        }

        $result = [];
        if ($autoItems !== []) {
            $autoSend = \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransitionMail::sendAutoTemplates(
                $candidateId,
                $projectId,
                $autoItems,
                $userId
            );
            if ($autoSend['sent'] > 0 || $autoSend['failed'] > 0) {
                $result['autoSend'] = $autoSend;
            }
        }
        if ($promptTemplateIds !== []) {
            $result['mailPrompt'] = [
                'candidateId' => $candidateId,
                'projectId' => $projectId,
                'templateIds' => $promptTemplateIds,
            ];
        }

        return $result;
    }
}
