<?php

namespace FreeCRM\Modules\KnowledgeBase\Models;

/**
 * Field Class for KnowledgeBase
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Field extends Model
{

	public function isAjaxEditable()
	{
		$edit = parent::isAjaxEditable();
		if ($edit && $this->getName() === 'knowledgebase_status') {
			$edit = false;
		}
		return $edit;
	}
}
