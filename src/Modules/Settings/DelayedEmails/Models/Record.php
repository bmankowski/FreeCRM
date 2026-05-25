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

class Record extends \App\Modules\Settings\Base\Models\Record
{
	public function getId()
	{
		return $this->get('id');
	}

	public function getName(): string
	{
		return (string) $this->get('subject');
	}

	public function getDisplayValue($fieldName)
	{
		switch ($fieldName) {
			case 'source_id':
				return $this->resolveRecordLabel((int) $this->get('source_id'));
			case 'dest_id':
				return $this->resolveRecordLabel((int) $this->get('dest_id'));
			case 'type':
				return \App\Runtime\Vtiger_Language_Handler::translate(
					'LBL_TYPE_' . strtoupper((string) $this->get('type')),
					'Settings:DelayedEmails'
				);
			case 'recipient':
				return $this->formatFirstRecipient();
			case 'send_after':
			case 'created_at':
				return \App\Fields\DateTimeField::convertToUserFormat((string) $this->get($fieldName));
			case 'actions':
				return $this->renderActionLinks();
			default:
				return (string) $this->get($fieldName);
		}
	}

	public function getRecordLinks(): array
	{
		return [];
	}

	private function resolveRecordLabel(int $recordId): string
	{
		if ($recordId <= 0) {
			return (string) $recordId;
		}
		try {
			$moduleName = \App\Utils\ModuleUtils::getModuleName($recordId);
			if (!$moduleName) {
				return (string) $recordId;
			}
			$record = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
			return $record->getName() . ' (' . $recordId . ')';
		} catch (\Throwable $e) {
			return (string) $recordId;
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

	private function renderActionLinks(): string
	{
		$id = (int) $this->getId();
		$cancelUrl = 'index.php?module=DelayedEmails&parent=Settings&action=Cancel&record=' . $id;
		$sendUrl = 'index.php?module=DelayedEmails&parent=Settings&action=SendNow&record=' . $id;
		$cancelLabel = \App\Runtime\Vtiger_Language_Handler::translate('LBL_CANCEL', 'Settings:DelayedEmails');
		$sendLabel = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEND_NOW', 'Settings:DelayedEmails');
		return '<a class="btn btn-danger btn-xs" href="' . $cancelUrl . '">' . $cancelLabel . '</a> '
			. '<a class="btn btn-primary btn-xs" href="' . $sendUrl . '">' . $sendLabel . '</a>';
	}
}
