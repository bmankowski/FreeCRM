<?php

namespace App\Modules\UiPrefix1781016817;

class UiPrefix1781016817 extends \App\Core\CRMEntity
{
	public $table_name = 'u_yf_uiprefix1781016817';
	public $table_index = 'uiprefix1781016817id';

	public $customFieldTable = ['u_yf_uiprefix1781016817cf', 'uiprefix1781016817id'];

	public $tab_name = ['vtiger_crmentity', 'u_yf_uiprefix1781016817', 'u_yf_uiprefix1781016817cf'];

	public $tab_name_index = [
		'vtiger_crmentity' => 'crmid',
		'u_yf_uiprefix1781016817' => 'uiprefix1781016817id',
		'u_yf_uiprefix1781016817cf' => 'uiprefix1781016817id',
	];

	public $list_fields_name = [
		'Label' => 'label',
		'Assigned To' => 'assigned_user_id',
	];

	public $search_fields = [
		'Label' => ['', 'label'],
	];

	public $search_fields_name = [
		'Label' => 'label',
	];

	public $popup_fields = ['label'];
	public $def_basicsearch_col = 'label';
	public $def_detailview_recname = 'label';
	public $mandatory_fields = ['label', 'assigned_user_id'];
}
