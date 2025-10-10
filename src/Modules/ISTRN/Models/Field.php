<?php

namespace FreeCRM\Modules\ISTRN\Models;

/**
 * Field Class for ISTRN
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends \FreeCRM\Modules\Vtiger\Models\Field
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'istrn_status') {
			$edit = false;
		}
		return $edit;
	}
}
