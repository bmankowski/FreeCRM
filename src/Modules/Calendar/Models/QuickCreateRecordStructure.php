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

namespace App\Modules\Calendar\Models;

/**
 * Calendar-specific quick-create record structure.
 *
 * Extends the base QuickCreateRecordStructure to provide sensible default
 * values for Calendar date/time fields (date_start, due_date, time_start,
 * time_end) when the record is new and no request value has been supplied.
 * Without this, DateTime.tpl receives null fieldvalues and emits PHP
 * deprecation warnings that collapse the form layout to 0px height.
 */
class QuickCreateRecordStructure extends \App\Modules\Base\Models\QuickCreateRecordStructure
{
	public function getStructure(): array
	{
		$values = parent::getStructure();

		$currentUser = \App\User\CurrentUser::get();
		$callDuration = $currentUser ? (int) $currentUser->get('callduration') : 15;

		foreach ($values as $fieldName => &$fieldModel) {
			if (!empty($fieldModel->get('fieldvalue'))) {
				continue;
			}
			switch ($fieldName) {
				case 'date_start':
					// DB datetime format so DateTime.tpl can split on the space and populate time_start
					$fieldModel->set('fieldvalue', date('Y-m-d H:i:s'));
					break;
				case 'due_date':
					// DB datetime format so DateTime.tpl can split on the space and populate time_end
					$fieldModel->set('fieldvalue', date('Y-m-d H:i:s', strtotime("+{$callDuration} minutes")));
					break;
				case 'time_start':
					$fieldModel->set('fieldvalue', date('H:i:s'));
					break;
				case 'time_end':
					$fieldModel->set('fieldvalue', date('H:i:s', strtotime("+{$callDuration} minutes")));
					break;
			}
		}
		unset($fieldModel);

		return $values;
	}
}
