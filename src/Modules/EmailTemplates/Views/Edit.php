<?php

namespace FreeCRM\Modules\EmailTemplates\Views;

class Edit extends \Vtiger_Index_View
{

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return array - List of \FreeCRM\Modules\Vtiger\Models\JsScript instances
	 */
	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
	{
		$parentScript = parent::getFooterScripts($request);
		$fileNames = [
			'libraries.jquery.clipboardjs.clipboard',
		];
		$scriptInstances = $this->checkAndConvertJsScripts($fileNames);
		return array_merge($parentScript, $scriptInstances);
	}
}
