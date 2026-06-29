<?php

namespace App\Modules\Users\UiTypes;

/**
 * UIType Boolean Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Boolean extends \App\Modules\Base\UiTypes\Boolean
{

	public function getDBValue($value, $recordModel = false)
	{
		if ($this->getFieldModel()->getFieldName() === 'is_admin') {
			if ($value === 'on' || $value === 1 || $value === '1' || $value === true) {
				return 'on';
			}
			return 'off';
		}
		return parent::getDBValue($value, $recordModel);
	}
}
