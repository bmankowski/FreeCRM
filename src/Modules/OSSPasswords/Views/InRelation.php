<?php

namespace FreeCRM\Modules\OSSPasswords\Views;

//The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html

class InRelation extends \Vtiger_Index_View
{

	public function getScripts(\FreeCRM\Http\Vtiger_Request $request)
	{
		$jsFileNames = [
			'libraries.jquery.clipboardjs.clipboard',
			'modules.OSSPasswords.resources.showRelatedModulePass',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('RELATED_SCRIPTS', $this->getScripts($request));
		return parent::process($request);
	}
}
