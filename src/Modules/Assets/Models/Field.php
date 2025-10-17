<?php

namespace App\Modules\Assets\Models;

/**
 *
 * @package YetiForce.models
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class Field extends \App\Modules\Vtiger\Models\Field
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'assetstatus') {
			$edit = false;
		}
		return $edit;
	}
}
