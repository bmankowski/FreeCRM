<?php

namespace App\Modules\Candidates\Widgets;

/**
 * Vtiger summary widget class.
 *
 * @package Widget
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 */
class CandidatesRecruitmentProjects extends \App\Modules\Base\Widgets\Basic
{
	public function getWidget()
	{
		$this->Config['tpl'] = 'CandidatesRecruitmentProjects.tpl';
		$candidatesId = $this->Record;

		/* @var \App\Modules\Candidates\Models\Record $candidate */
		$candidate = \App\Modules\Candidates\Models\Record::getInstanceById($candidatesId, 'Candidates');
		$recruitmentProjectsData = $candidate->getRecruitmentProjectsWithStatus();
		$projects = [];
		foreach ($recruitmentProjectsData as $projectData){
			$projectId = $projectData['project_id'];
			$project['id'] = $projectId;
			$project['url'] = 'index.php?module=ProjektyRekrutacyjne&view=Detail&record='.$projectId;
			$project['name'] = $projectData['nazwa_projektu'];
			$project['status'] = $projectData['recruitment_status_rel'];
			$project['comment'] = $projectData['comment_rel'];
			$project['created_time'] = $projectData['rel_created_time'];
			$project['created_user'] = $projectData['rel_created_user'];
			$projects[] = $project;
		}


		$this->Config['data']['recruitmentProjects'] = $projects;
		$this->Config['data']['candidatesId'] = $candidatesId;

		return $this->Config;
	}

	public function getConfigTplName()
	{
		return 'CandidatesRecruitmentProjectsConfig';
	}
}
