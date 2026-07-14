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

namespace App\Modules\DelayedEmails\Models;

class Module extends \App\Modules\Base\Models\Module
{
	public $name = 'DelayedEmails';
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
	protected array $virtualListFields = ['actions', 'recipient'];
	protected ?array $listFieldModels = null;

	public function getDefaultUrl(): string
	{
		return 'index.php?module=DelayedEmails&view=ListView';
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

	public function getBaseTable(): string
	{
		return $this->baseTable;
	}

	public function getBaseIndex(): string
	{
		return $this->baseIndex;
	}

	public function getListFields(): array
	{
		if ($this->listFieldModels === null) {
			$fields = $this->listFields;
			$user = \App\Modules\Users\Models\Record::getCurrentUserModel();
			if ($user === null || !$user->isAdminUser()) {
				unset($fields['actions']);
			}
			$fieldObjects = [];
			foreach ($fields as $fieldName => $fieldLabel) {
				$fieldObjects[$fieldName] = new \App\Runtime\BaseModel(['name' => $fieldName, 'label' => $fieldLabel]);
			}
			$this->listFieldModels = $fieldObjects;
		}
		return $this->listFieldModels;
	}

	public function getQueryableListFields(): array
	{
		return array_values(array_diff(array_keys($this->listFields), $this->virtualListFields));
	}

	public function isVirtualListField(string $fieldName): bool
	{
		return in_array($fieldName, $this->virtualListFields, true);
	}
}
