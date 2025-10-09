<?php

namespace FreeCRM\Modules\ISTDN\Models;

/**
 * Field Class for ISTDN
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends Model
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'istdn_status') {
			$edit = false;
		}
		return $edit;
	}
}
