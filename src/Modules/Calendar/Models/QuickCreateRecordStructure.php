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
 * values for start date/time when the record is new and no request value
 * has been supplied. End date/time stay empty until the user focuses due_date
 * (Calendar_Edit_Js) or picks a calendar slot (CalendarView.js).
 */
class QuickCreateRecordStructure extends \App\Modules\Base\Models\QuickCreateRecordStructure
{
	public function getStructure(): array
	{
		$values = parent::getStructure();

		foreach ($values as $fieldName => &$fieldModel) {
			if (!empty($fieldModel->get('fieldvalue'))) {
				continue;
			}
			switch ($fieldName) {
				case 'date_start':
					$fieldModel->set('fieldvalue', date('Y-m-d H:i:s'));
					break;
				case 'time_start':
					$fieldModel->set('fieldvalue', date('H:i:s'));
					break;
			}
		}
		unset($fieldModel);

		return $values;
	}
}
