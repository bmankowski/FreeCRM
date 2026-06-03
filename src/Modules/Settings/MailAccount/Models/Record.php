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
	protected array $data = [];

	public function getId(): int
	{
		return (int) ($this->data['id'] ?? 0);
	}

	public function getName(): string
	{
		return (string) ($this->data['name'] ?? '');
	}

	public function get(string $key)
	{
		return $this->data[$key] ?? null;
	}

	public function set(string $key, $value): self
	{
		$this->data[$key] = $value;
		return $this;
	}

	public function setData(array $data): self
	{
		$this->data = $data;
		return $this;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public static function getInstanceById(int $id): ?self
	{
		$row = \App\Modules\Mail\Models\Account::getById($id);
		if ($row === null) {
			return null;
		}
		$raw = (new \App\Db\Query())->from('u_yf_mail_account_users')->where(['account_id' => $id])->all();
		$row['assigned_users'] = array_column($raw, 'user_id');
		$model = new self();
		$model->setData($row);
		return $model;
	}

	public static function getCleanInstance(): self
	{
		$model = new self();
		$model->setData([
			'kind' => 'shared',
			'imap_port' => 993,
			'imap_secure' => 'ssl',
			'smtp_port' => 465,
			'smtp_secure' => 'ssl',
			'append_sent' => 1,
			'assigned_users' => [],
		]);
		return $model;
	}

	public function getEditViewUrl(): string
	{
		return 'index.php?module=MailAccount&parent=Settings&view=Edit&record=' . $this->getId();
	}

	public function getDeleteActionUrl(): string
	{
		return 'index.php?module=MailAccount&parent=Settings&action=DeleteAjax&record=' . $this->getId();
	}

	public function save(): void
	{
		$userIds = array_map('intval', (array) ($this->data['assigned_users'] ?? []));
		$saved = \App\Modules\Mail\Models\Account::saveShared(
			$this->data,
			$this->getId() ?: null,
			$userIds,
			!empty($this->data['activate'])
		);
		$this->data = $saved;
	}
}
