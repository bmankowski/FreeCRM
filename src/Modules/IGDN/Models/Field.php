<?php

namespace FreeCRM\Modules\IGDN\Models;

/**
 * Field Class for IGDN
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends \FreeCRM\Modules\Vtiger\Models\Field
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'igdn_status') {
			$edit = false;
		}
		return $edit;
	}
}
