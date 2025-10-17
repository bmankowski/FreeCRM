<?php

namespace App\Modules\IIDN\Models;

/**
 * Field Class for IIDN
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends \App\Modules\Vtiger\Models\Field
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'iidn_status') {
			$edit = false;
		}
		return $edit;
	}
}
