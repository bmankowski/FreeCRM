<?php

namespace App\Modules\DocumentTemplates\UiTypes;

class WatermarkImage extends \App\Modules\Base\UiTypes\BaseUiType
{
	public function getTemplateName()
	{
		return 'uitypes/document_template_watermark.tpl';
	}

	public function isAjaxEditable()
	{
		return false;
	}
}
