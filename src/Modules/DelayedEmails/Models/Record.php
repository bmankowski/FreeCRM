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

class Record extends \App\Runtime\BaseModel
{
	private ?Module $moduleModel = null;

	public function setModule(Module $module): self
	{
		$this->moduleModel = $module;
		return $this;
	}

	public function getId()
	{
		return $this->get('id');
	}

	public function getName(): string
	{
		return (string) $this->get('subject');
	}

	public function getLinkedRecord(string $fieldName): ?\App\Modules\Base\Models\Record
	{
		if ($fieldName !== 'source_id' && $fieldName !== 'dest_id') {
			return null;
		}
		$recordId = (int) $this->get($fieldName);
		if ($recordId <= 0) {
			return null;
		}
		try {
			$record = \App\Modules\Base\Models\Record::getInstanceById($recordId);
			return $record->isViewable() ? $record : null;
		} catch (\Throwable) {
			return null;
		}
	}

	public function getDisplayValue(string $fieldName): string
	{
		switch ($fieldName) {
			case 'source_id':
			case 'dest_id':
				return $this->resolveRecordLabel((int) $this->get($fieldName));
			case 'type':
				return \App\Runtime\Vtiger_Language_Handler::translate(
					'LBL_TYPE_' . strtoupper((string) $this->get('type')),
					'DelayedEmails'
				);
			case 'recipient':
				return $this->formatFirstRecipient();
			case 'send_after':
			case 'created_at':
				return \App\Fields\DateTimeField::convertToUserFormat((string) $this->get($fieldName));
			default:
				return (string) $this->get($fieldName);
		}
	}

	public function getRecordLinks(): array
	{
		$user = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if ($user === null || !$user->isAdminUser()) {
			return [];
		}

		$id = (int) $this->getId();
		$recordLinks = [
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_SEND_NOW',
				'linkurl' => 'index.php?module=DelayedEmails&action=SendNow&record=' . $id,
				'linkicon' => 'glyphicon glyphicon-send',
				'linkclass' => 'btn btn-xs btn-success',
			],
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_CANCEL',
				'linkurl' => 'index.php?module=DelayedEmails&action=Cancel&record=' . $id,
				'linkicon' => 'glyphicon glyphicon-remove',
				'linkclass' => 'btn btn-xs btn-danger',
			],
		];
		$links = [];
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}
		return $links;
	}

	private function resolveRecordLabel(int $recordId): string
	{
		if ($recordId <= 0) {
			return (string) $recordId;
		}
		try {
			$record = \App\Modules\Base\Models\Record::getInstanceById($recordId);
			return $record->getName();
		} catch (\Throwable) {
			$moduleName = \App\Records\Record::getType($recordId);
			return $moduleName ? $moduleName . ' #' . $recordId : (string) $recordId;
		}
	}

	private function formatFirstRecipient(): string
	{
		$decoded = \App\Utils\Json::decode((string) $this->get('recipients_json'));
		$to = $decoded['to'] ?? [];
		if (!is_array($to) || $to === []) {
			return '-';
		}
		foreach ($to as $email => $name) {
			if (is_numeric($email)) {
				return (string) $name;
			}
			return (string) $email;
		}
		return '-';
	}
}
