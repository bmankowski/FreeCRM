<?php

namespace App\Modules\Users\UiTypes;

/**
 * UIType Boolean Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Boolean extends \App\Modules\Vtiger\UiTypes\Base
{

	public function getDBValue($value, $recordModel = false)
	{
		if ($this->getFieldModel()->getFieldName() === 'is_admin') {
			if ($value === 'on' || $value === 1) {
				return 'on';
			} else {
				return 'off';
			}
		}
		return parent::getDBValue($value, $recordModel);
	}
}
