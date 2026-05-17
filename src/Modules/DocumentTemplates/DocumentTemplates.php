<?php

namespace App\Modules\DocumentTemplates;

/**
 * DocumentTemplates CRMEntity
 *
 * @package FreeCRM
 * @license FreeCRM Public License 1.1
 */
class DocumentTemplates extends \App\Core\CRMEntity
{
	public $table_name = 'u_yf_documenttemplates';
	public $table_index = 'documenttemplatesid';

	public $customFieldTable = [];

	public $tab_name = ['vtiger_crmentity', 'u_yf_documenttemplates'];

	public $tab_name_index = [
		'vtiger_crmentity' => 'crmid',
		'u_yf_documenttemplates' => 'documenttemplatesid',
	];

	public $list_fields = [
		'LBL_PRIMARY_NAME' => ['documenttemplates', 'primary_name'],
		'Assigned To' => ['crmentity', 'smownerid'],
	];
	public $list_fields_name = [
		'LBL_PRIMARY_NAME' => 'primary_name',
		'Assigned To' => 'assigned_user_id',
	];
	public $list_link_field = 'primary_name';
	public $search_fields = [
		'LBL_PRIMARY_NAME' => ['documenttemplates', 'primary_name'],
		'Assigned To' => ['vtiger_crmentity', 'assigned_user_id'],
	];
	public $search_fields_name = [
		'LBL_PRIMARY_NAME' => 'primary_name',
		'Assigned To' => 'assigned_user_id',
	];
	public $popup_fields = ['primary_name'];
	public $def_basicsearch_col = 'primary_name';
	public $def_detailview_recname = 'primary_name';
	public $mandatory_fields = ['primary_name', 'assigned_user_id'];
	public $default_order_by = '';
	public $default_sort_order = 'ASC';
}
