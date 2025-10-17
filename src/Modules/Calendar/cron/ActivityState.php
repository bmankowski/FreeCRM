<?php

namespace App\Modules\Calendar\cron;
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */

$statusActivity = \App\Modules\Calendar\Models\Module::getComponentActivityStateLabel();
$dataReader = (new \App\Db\Query())->select(['vtiger_activity.activityid', 'vtiger_activity.due_date', 'vtiger_activity.time_end',
			'vtiger_activity.date_start', 'vtiger_activity.time_start', 'activitystatus' => 'vtiger_activity.status'])
		->from('vtiger_activity')
		->innerJoin(['crm' => 'vtiger_crmentity'], 'crm.crmid = vtiger_activity.activityid')
		->where(['vtiger_activity.status' => [$statusActivity['not_started'], $statusActivity['in_realization']], 'crm.deleted' => 0, 'crm.setype' => 'Calendar'])
		->limit(\App\AppConfig::module('Calendar', 'CRON_MAX_NUMBERS_ACTIVITY_STATE'))
		->createCommand()->query();
while ($row = $dataReader->read()) {
	$state = \App\Modules\Calendar\Models\Module::getCalendarState($row);
	if ($state && $state != $row['activitystatus']) {
		$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($row['activityid']);
		$recordModel->set('id', $row['activityid']);
		$recordModel->set('activitystatus', $state);
		$recordModel->save();
	}
}

