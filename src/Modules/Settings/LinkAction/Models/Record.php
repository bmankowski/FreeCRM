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

class Record extends \App\Modules\Settings\Base\Models\Record
{
	public function getId()
	{
		return $this->get('id');
	}

	public function getName(): string
	{
		return (string) $this->getId();
	}

	public function getDisplayValue(string $fieldName): string
	{
		switch ($fieldName) {
			case 'clicked_at':
			case 'processed_at':
				return $this->formatDateTime((string) $this->get($fieldName));
			case 'module':
				return \App\Runtime\Vtiger_Language_Handler::translate(
					(string) $this->get('module'),
					(string) $this->get('module')
				);
			case 'record_id':
				return $this->resolveRecordLabel((string) $this->get('module'), (int) $this->get('record_id'));
			case 'send_subject':
				$subject = trim((string) $this->get('send_subject'));
				$mailId = (int) $this->get('mail_message_row_id');
				if ($mailId <= 0) {
					$mailId = (int) $this->get('mail_message_id');
				}
				if ($subject === '' && $mailId <= 0) {
					return '---';
				}
				$label = $subject !== '' ? $subject : '---';
				if ($mailId > 0) {
					return $label . ' (mail #' . $mailId . ')';
				}
				return $label;
			case 'action':
				return \App\Runtime\Vtiger_Language_Handler::translate(
					'LBL_LINK_ACTION_ACTION_' . strtoupper((string) $this->get('action')),
					'Settings:LinkAction'
				);
			case 'scope':
				return \App\Runtime\Vtiger_Language_Handler::translate(
					'LBL_LINK_ACTION_SCOPE_' . strtoupper((string) $this->get('scope')),
					'Settings:LinkAction'
				);
			case 'token_fp':
				$fp = (string) $this->get('token_fp');
				return strlen($fp) > 16 ? substr($fp, 0, 16) . '…' : $fp;
			default:
				return (string) ($this->get($fieldName) ?? '');
		}
	}

	private function formatDateTime(string $value): string
	{
		if ($value === '' || $value === '0000-00-00 00:00:00') {
			return '---';
		}
		return \App\Modules\Base\UiTypes\Datetime::getDateTimeValue($value);
	}

	private function resolveRecordLabel(string $moduleName, int $recordId): string
	{
		if ($recordId <= 0) {
			return (string) $recordId;
		}
		try {
			$record = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
			return $record->getName() . ' (' . $recordId . ')';
		} catch (\Throwable) {
			return $moduleName . ' #' . $recordId;
		}
	}
}
