<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Settings\LinkAction\Models;

class Module extends \App\Modules\Settings\Base\Models\Module
{
	public $baseTable = 'u_yf_link_action_log';
	public $baseIndex = 'id';
	public $listFields = [
		'clicked_at' => 'LBL_CLICKED_AT',
		'module' => 'LBL_MODULE',
		'record_id' => 'LBL_RECORD',
		'send_subject' => 'LBL_LINK_ACTION_SEND_SUBJECT',
		'action' => 'LBL_ACTION',
		'scope' => 'LBL_SCOPE',
		'email_field' => 'LBL_EMAIL_FIELD',
		'processed_at' => 'LBL_PROCESSED_AT',
		'token_fp' => 'LBL_TOKEN_FP',
	];
	public $name = 'LinkAction';

	public function getDefaultUrl(): string
	{
		return 'index.php?module=LinkAction&parent=Settings&view=ListView';
	}

	public function getCreateRecordUrl(): string
	{
		return '';
	}

	public function hasCreatePermissions(): bool
	{
		return false;
	}
}
