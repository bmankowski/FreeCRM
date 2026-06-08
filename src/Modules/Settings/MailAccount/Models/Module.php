<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Settings\MailAccount\Models;

class Module extends \App\Modules\Settings\Base\Models\Module
{
	public $baseTable = 'u_yf_mail_accounts';
	public $baseIndex = 'id';
	public $nameFields = ['name'];
	protected array $virtualListFields = ['group_name', 'owner_name'];
	public $listFields = [
		'name' => 'LBL_NAME',
		'kind' => 'LBL_KIND',
		'owner_name' => 'LBL_OWNER_USER',
		'group_name' => 'LBL_CRM_GROUP',
		'username' => 'LBL_USERNAME',
		'active' => 'LBL_ACTIVE',
		'last_scan_at' => 'LBL_LAST_SCAN',
		'last_scan_status' => 'LBL_STATUS',
	];
	public $name = 'MailAccount';

	public function getDefaultUrl(): string
	{
		return 'index.php?module=MailAccount&parent=Settings&view=List';
	}

	public function getListViewUrl(): string
	{
		return $this->getDefaultUrl();
	}

	public function getCreateRecordUrl(): string
	{
		return 'index.php?module=MailAccount&parent=Settings&view=Edit';
	}

	public function hasCreatePermissions(): bool
	{
		return true;
	}

	public function isPagingSupported(): bool
	{
		return false;
	}
}
