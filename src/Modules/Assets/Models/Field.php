<?php

namespace FreeCRM\Modules\Assets\Models;

/**
 *
 * @package YetiForce.models
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class Field extends Model
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
