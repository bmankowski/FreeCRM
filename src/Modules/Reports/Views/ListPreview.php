<?php

namespace App\Modules\Reports\Views;

/**
 * Reports list with split preview — uses Reports list data, not Base ListView query.
 */
class ListPreview extends ListView
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, $display);
		if ($request->isAjax()) {
			return;
		}
		$request->getUser()->setPreference('ListViewDefaultView_' . $request->getModule(), 'ListPreview');
		$this->getViewer($request)->assign('LIST_PREVIEW_MODE', true);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$scripts = parent::getFooterScripts($request);
		$scriptPath = 'layouts/basic/modules/Base/resources/ListPreview.js';
		$scriptVersion = \is_file(ROOT_DIRECTORY . '/' . $scriptPath) ? \filemtime(ROOT_DIRECTORY . '/' . $scriptPath) : time();
		$scripts['modules.Base.resources.ListPreview'] = (new \App\View\Assets\ScriptAsset())->set('src', $scriptPath . '?v=' . $scriptVersion);
		return $scripts;
	}
}
