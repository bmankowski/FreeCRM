<?php

namespace App\Modules\Base\Views;

/**
 * ListPreview view (split list + record summary preview)
 *
 * Keeps maximum compatibility by reusing ListView logic and templates.
 */
class ListPreview extends \App\Modules\Base\Views\ListView
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, $display);
		// For AJAX list reloads we only render ListViewContents.tpl, no need for preview flag.
		if ($request->isAjax()) {
			return;
		}
		// Persist last list display mode per user + module
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

