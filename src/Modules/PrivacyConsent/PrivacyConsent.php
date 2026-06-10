<?php

namespace App\Modules\PrivacyConsent;

class PrivacyConsent extends \App\Core\CRMEntity
{
	public $table_name = 'u_yf_privacyconsent';
	public $table_index = 'privacyconsentid';

	public $customFieldTable = ['u_yf_privacyconsentcf', 'privacyconsentid'];

	public $tab_name = ['vtiger_crmentity', 'u_yf_privacyconsent', 'u_yf_privacyconsentcf'];

	public $tab_name_index = [
		'vtiger_crmentity' => 'crmid',
		'u_yf_privacyconsent' => 'privacyconsentid',
		'u_yf_privacyconsentcf' => 'privacyconsentid',
	];

	public $list_fields_name = [
		'Label' => 'label',
		'Assigned To' => 'assigned_user_id',
	];

	public $search_fields = [
		'Label' => ['privacyconsent', 'label'],
	];

	public $search_fields_name = [
		'Label' => 'label',
	];

	public $popup_fields = ['label'];
	public $def_basicsearch_col = 'label';
	public $def_detailview_recname = 'label';
	public $mandatory_fields = ['label', 'assigned_user_id'];
}
