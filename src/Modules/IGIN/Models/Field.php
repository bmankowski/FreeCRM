<?php

namespace App\Modules\IGIN\Models;

/**
 * Field Class for IGIN
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends \App\Modules\Vtiger\Models\Field
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'igin_status') {
			$edit = false;
		}
		return $edit;
	}
}
