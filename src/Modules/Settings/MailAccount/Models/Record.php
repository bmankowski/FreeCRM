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

class Record extends \App\Modules\Settings\Base\Models\Record
{
	/** @var \App\Modules\Settings\MailAccount\Models\Module|null */
	protected $module;

	public function getId()
	{
		return (int) $this->get('id');
	}

	public function getName()
	{
		return (string) ($this->get('name') ?? '');
	}

	public static function getInstanceById(int $id): ?self
	{
		$row = \App\Modules\Mail\Models\Account::getById($id);
		if ($row === null) {
			return null;
		}

		return new self($row);
	}

	public static function getCleanInstance(): self
	{
		return new self([
			'kind' => 'group',
			'imap_port' => 993,
			'imap_secure' => 'ssl',
			'smtp_port' => 465,
			'smtp_secure' => 'ssl',
			'append_sent' => 1,
			'group_id' => null,
		]);
	}

	public function getModule()
	{
		return $this->module;
	}

	public function setModule(\App\Modules\Settings\Base\Models\Module $module): self
	{
		$this->module = $module;
		return $this;
	}

	public function getEditViewUrl(): string
	{
		return 'index.php?module=MailAccount&parent=Settings&view=Edit&record=' . $this->getId();
	}

	public function getDetailViewUrl(): string
	{
		return $this->getEditViewUrl();
	}

	public function getDisplayValue(string $key): string
	{
		$value = $this->get($key);
		switch ($key) {
			case 'active':
				return \App\Runtime\Vtiger_Language_Handler::translate(
					((int) $value === 1) ? 'LBL_YES' : 'LBL_NO',
					'Vtiger'
				);
			case 'kind':
				$label = 'LBL_KIND_' . strtoupper((string) $value);
				$translated = \App\Runtime\Vtiger_Language_Handler::translate($label, 'Settings:MailAccount');
				return $translated !== $label ? $translated : (string) $value;
			case 'group_name':
				return (string) ($value ?: '-');
			case 'owner_name':
				return (string) ($value ?: '-');
			case 'last_scan_at':
				return $value ? (string) $value : '-';
			case 'last_scan_status':
				if ($value === 'ok') {
					return 'OK';
				}
				if ($value === 'error') {
					return 'Error (' . (int) ($this->get('consecutive_failures') ?? 0) . ')';
				}
				if ($value === 'disabled') {
					return 'off';
				}
				return '-';
			default:
				return (string) ($value ?? '');
		}
	}

	public function getRecordLinks(): array
	{
		return [];
	}

	public function getDeleteActionUrl(): string
	{
		return 'index.php?module=MailAccount&parent=Settings&action=DeleteAjax&record=' . $this->getId();
	}

	public function save(): void
	{
		$saved = \App\Modules\Mail\Models\Account::saveGroup(
			$this->getData(),
			$this->getId() ?: null,
			[],
			!empty($this->get('activate'))
		);
		$this->setData($saved);
	}
}
