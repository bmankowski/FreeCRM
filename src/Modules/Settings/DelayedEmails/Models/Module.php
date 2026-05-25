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

namespace App\Modules\Settings\DelayedEmails\Models;

class Module extends \App\Modules\Settings\Base\Models\Module
{
	public $baseTable = 's_#__delayed_email_queue';
	public $baseIndex = 'id';
	public $listFields = [
		'source_id' => 'LBL_SOURCE',
		'dest_id' => 'LBL_DESTINATION',
		'type' => 'LBL_TYPE',
		'recipient' => 'LBL_RECIPIENT',
		'subject' => 'LBL_SUBJECT',
		'send_after' => 'LBL_SEND_AFTER',
		'created_at' => 'LBL_CREATED_AT',
		'actions' => 'LBL_ACTIONS',
	];
	public $name = 'DelayedEmails';

	public function getDefaultUrl(): string
	{
		return 'index.php?module=DelayedEmails&parent=Settings&view=ListView';
	}

	public function getCreateRecordUrl(): string
	{
		return '';
	}

	public function hasCreatePermissions(): bool
	{
		return false;
	}

	public function isPagingSupported(): bool
	{
		return false;
	}
}
