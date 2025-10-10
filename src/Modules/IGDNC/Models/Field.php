<?php

namespace FreeCRM\Modules\IGDNC\Models;

/**
 * Field Class for IGDNC
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends \FreeCRM\Modules\Vtiger\Models\Field
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'igdnc_status') {
			$edit = false;
		}
		return $edit;
	}
}
