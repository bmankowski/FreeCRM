<?php

namespace FreeCRM\Modules\SSalesProcesses\Models;

/**
 * Field Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends Model
{

	/**
	 * Function returns special validator for fields
	 * @return array
	 */
	public function getValidator()
	{
		$validator = [];
		$fieldName = $this->getName();

		switch ($fieldName) {
			case 'estimated_date':
				$validator[] = ['name' => 'greaterThanDependentField',
					'params' => ['startdate', 'estimated_date']];
				break;
			default : $validator = parent::getValidator();
				break;
		}
		return $validator;
	}
}
