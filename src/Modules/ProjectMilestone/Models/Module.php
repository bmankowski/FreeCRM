<?php

namespace App\Modules\ProjectMilestone\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Module extends \App\Modules\Base\Models\Module
{

	/**
	 * Function to get list view query for popup window
	 * @param \App\Modules\Base\Models\ListView $listviewModel
	 * @param \App\QueryGenerator $queryGenerator
	 */
	public function getQueryByRelatedField(\App\Modules\Base\Models\ListView $listviewModel, \App\QueryGenerator $queryGenerator)
	{
		if ($listviewModel->get('src_module') === 'Project' && !$listviewModel->isEmpty('filterFields')) {
			$filterFields = $listviewModel->get('filterFields');
			if (!empty($filterFields['projectid'])) {
				$queryGenerator->addNativeCondition(['projectid' => $filterFields['projectid']]);
			}
		}
	}

	public function updateProgressMilestone($id)
	{
		if (!\App\Records\Record::isExists($id)) {
			return;
		}
		$relatedListView = \App\Modules\Base\Models\RelationListView::getInstance(\App\Modules\Base\Models\Record::getInstanceById($id), 'ProjectTask');
		$relatedListView->getRelationModel()->set('QueryFields', [
			'estimated_work_time' => 'estimated_work_time',
			'projecttaskprogress' => 'projecttaskprogress',
		]);
		$dataReader = $relatedListView->getRelationQuery()->createCommand()->query();
		$estimatedWorkTime = 0;
		$progressInHours = 0;
		while ($row = $dataReader->read()) {
			$estimatedWorkTime += $row['estimated_work_time'];
			$recordProgress = ($row['estimated_work_time'] * (int) $row['projecttaskprogress']) / 100;
			$progressInHours += $recordProgress;
		}
		if (!$estimatedWorkTime) {
			return;
		}
		$projectMilestoneProgress = round((100 * $progressInHours) / $estimatedWorkTime);
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($id, $this->getName());
		$recordModel->set('projectmilestone_progress', $projectMilestoneProgress . '%');
		$recordModel->save();
	}
}
