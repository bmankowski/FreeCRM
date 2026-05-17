<?php

namespace App\Modules\TemplateElements;

class TemplateElements extends \App\Core\CRMEntity
{
	public $table_name = 'u_yf_templateelements';
	public $table_index = 'templateelementsid';

	public $customFieldTable = [];

	public $tab_name = ['vtiger_crmentity', 'u_yf_templateelements'];

	public $tab_name_index = [
		'vtiger_crmentity' => 'crmid',
		'u_yf_templateelements' => 'templateelementsid',
	];

	public $list_fields = [
		'LBL_LABEL' => ['templateelements', 'label'],
		'Assigned To' => ['crmentity', 'smownerid'],
	];
	public $list_fields_name = [
		'LBL_LABEL' => 'label',
		'Assigned To' => 'assigned_user_id',
	];
	public $list_link_field = 'label';
	public $search_fields = [
		'LBL_LABEL' => ['templateelements', 'label'],
		'LBL_CODE' => ['templateelements', 'code'],
	];
	public $search_fields_name = [
		'LBL_LABEL' => 'label',
		'LBL_CODE' => 'code',
	];
	public $popup_fields = ['label'];
	public $def_basicsearch_col = 'label';
	public $def_detailview_recname = 'label';
	public $mandatory_fields = ['label', 'code', 'assigned_user_id'];
	public $default_order_by = 'sequence';
	public $default_sort_order = 'ASC';
}
