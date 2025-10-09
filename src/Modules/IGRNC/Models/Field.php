<?php

namespace FreeCRM\Modules\IGRNC\Models;

/**
 * Field Class for IGRNC
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends Model
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'igrnc_status') {
			$edit = false;
		}
		return $edit;
	}
}
