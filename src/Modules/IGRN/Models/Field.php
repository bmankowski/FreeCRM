<?php

namespace App\Modules\IGRN\Models;

/**
 * Field Class for IGRN
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends \App\Modules\Base\Models\Field
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'igrn_status') {
			$edit = false;
		}
		return $edit;
	}
}
