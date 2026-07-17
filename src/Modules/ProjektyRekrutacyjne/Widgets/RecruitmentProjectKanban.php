<?php

namespace App\Modules\ProjektyRekrutacyjne\Widgets;

/* +***********************************************************************************
 * ProjektyRekrutacyjne summary widget class.
 *
 * @package Widget
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 * *********************************************************************************** */

class RecruitmentProjectKanban extends \App\Modules\Base\Widgets\Basic
{
    public function getWidget()
    {
        $this->Config['tpl'] = 'RecruitmentProjectKanban.tpl';
        $projectId = $this->Record;

        /** @var \App\Modules\ProjektyRekrutacyjne\Models\Record $project */
        $project = \App\Modules\ProjektyRekrutacyjne\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne');
        $candidatesByStatus = $project->getRelatedCandidates();

        foreach ($candidatesByStatus as $status => $candidates) {
            //if status in table ('PPL_APPLIED', 'PPL_SENT_TO_CLIENT', 'PPL_REJECTED_BY_CLIENT', 'PPL_OFFER_REJECTED_BY_CANDIDATE', 'PPL_ACCEPTED')
            $candidatesByStatus[$status] = $candidates;
        }
        $this->Config['data']['candidatesByStatus'] = $candidatesByStatus;
        $this->Config['data']['projectId'] = $projectId;
        $this->Config['data']['cvBooleanQuery'] = trim((string) $project->get('cv_boolean_query'));
        $this->Config['data']['statusTransitions'] = [
            'configured' => \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::isConfigured(),
            'transitions' => \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::getAdjacencyMap(),
        ];
        $this->Config['data']['statusTransitionsJson'] = \App\Security\Purifier::encodeHtml(
            \App\Utils\Json::encode($this->Config['data']['statusTransitions'])
        );

        return $this->Config;
    }

    public function getConfigTplName()
    {
        return 'RecruitmentProjectKanbanConfig';
    }
}
