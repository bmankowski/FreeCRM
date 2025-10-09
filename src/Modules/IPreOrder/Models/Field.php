<?php

namespace FreeCRM\Modules\IPreOrder\Models;

/**
 * Field Class for IPreOrder
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends Model
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'ipreorder_status') {
			$edit = false;
		}
		return $edit;
	}
}
