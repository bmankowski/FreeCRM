<?php

/**
 * Vtiger summary widget class.
 *
 * @package Widget
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 */
class ProjektyRekrutacyjne_RecruitmentProjectKanban_Widget extends Vtiger_Basic_Widget
{
	public function getWidget()
	{
//		$this->Config['url'] =
		\App\Log::warning('ProjektyRekrutacyjne_Recruitm	entProjectKanban_Widget');
		$this->Config['tpl'] = 'RecruitmentProjectKanban.tpl';
		$projectId= $this->Record;
		$project = Vtiger_Record_Model::getInstanceById($projectId, 'ProjektyRekrutacyjne');
		$candidatesByStatus = $project->getRelatedCandidates();

		foreach ($candidatesByStatus as $status => $candidates) {
			//if status in table ('PPL_APPLIED', 'PPL_SENT_TO_CLIENT', 'PPL_REJECTED_BY_CLIENT', 'PPL_OFFER_REJECTED_BY_CANDIDATE', 'PPL_ACCEPTED')
			$candidatesByStatus[$status] = $candidates;
		}
		$this->Config['data']['candidatesByStatus'] = $candidatesByStatus;
		$this->Config['data']['projectId'] = $projectId;

		return $this->Config;
	}

	public function getConfigTplName()
	{
		return 'RecruitmentProjectKanbanConfig';
	}
}
