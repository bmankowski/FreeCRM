<?php

namespace App\Modules\Kandydaci\Widgets;

/**
 * Vtiger summary widget class.
 *
 * @package Widget
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 */
class KandydaciRecruitmentProjects extends \App\Modules\Base\Widgets\Basic
{
	public function getWidget()
	{
		$this->Config['tpl'] = 'KandydaciRecruitmentProjects.tpl';
		$kandydaciId = $this->Record;

		/* @var \App\Modules\Kandydaci\Models\Record $kandydat */
		$kandydat = \App\Modules\Kandydaci\Models\Record::getInstanceById($kandydaciId, 'Kandydaci');
		$recruitmentProjectsData = $kandydat->getRecruitmentProjectsWithStatus();
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
		$this->Config['data']['kandydaciId'] = $kandydaciId;

		return $this->Config;
	}

	public function getConfigTplName()
	{
		return 'KandydaciRecruitmentProjectsConfig';
	}
}
