<?php

namespace App\Modules\OSSSoldServices\Models;

/**
 * Field Class for OSSSoldServices
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Field extends \App\Modules\Base\Models\Field
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'ssservicesstatus') {
			$edit = false;
		}
		return $edit;
	}
}
