<?php

namespace App\Modules\DocumentTemplates\UiTypes;

class TemplateMembers extends \App\Modules\Base\UiTypes\BaseUiType
{
	public function getTemplateName()
	{
		return 'uitypes/document_template_members.tpl';
	}

	public function isAjaxEditable()
	{
		return false;
	}
}
