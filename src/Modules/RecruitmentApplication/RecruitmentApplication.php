<?php

namespace App\Modules\RecruitmentApplication;

class RecruitmentApplication extends \App\Core\CRMEntity
{
	public $table_name = 'vtiger_recruitmentapplication';
	public $table_index = 'recruitmentapplicationid';

	public $customFieldTable = ['vtiger_recruitmentapplicationcf', 'recruitmentapplicationid'];

	public $tab_name = ['vtiger_crmentity', 'vtiger_recruitmentapplication', 'vtiger_recruitmentapplicationcf'];

	public $tab_name_index = [
		'vtiger_crmentity' => 'crmid',
		'vtiger_recruitmentapplication' => 'recruitmentapplicationid',
		'vtiger_recruitmentapplicationcf' => 'recruitmentapplicationid',
	];

	public $list_fields_name = [
		'FL_APPLICATION_NUMBER' => 'application_number',
		'Assigned To' => 'assigned_user_id',
	];

	public $search_fields = [
		'FL_APPLICATION_NUMBER' => ['recruitmentapplication', 'application_number'],
	];

	public $search_fields_name = [
		'FL_APPLICATION_NUMBER' => 'application_number',
	];

	public $popup_fields = ['application_number'];
	public $def_basicsearch_col = 'application_number';
	public $def_detailview_recname = 'application_number';
	public $mandatory_fields = ['application_number', 'assigned_user_id'];
}
