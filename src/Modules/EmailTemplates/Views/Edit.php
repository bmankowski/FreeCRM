<?php

namespace App\Modules\EmailTemplates\Views;

class Edit extends \App\Modules\Base\Views\Edit
{

	protected function assignEditViewData(\App\Http\Vtiger_Request $request)
	{
		parent::assignEditViewData($request);
		$viewer = $this->getViewer($request);
		$viewer->assign('MAIL_ATTACHMENT_LIMITS', [
			'maxFileBytes' => \App\Modules\Mail\Models\ComposeAttachment::maxFileBytes(),
			'maxTotalBytes' => \App\Modules\Mail\Models\ComposeAttachment::maxTotalBytes(),
			'maxFiles' => \App\Modules\Mail\Models\ComposeAttachment::maxFiles(),
		]);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$parentScript = parent::getFooterScripts($request);
		$fileNames = [
			'libraries.jquery.clipboardjs.clipboard',
			'libraries.codemirror.lib.codemirror',
			'libraries.codemirror.mode.xml.xml',
			'libraries.codemirror.mode.javascript.javascript',
			'libraries.codemirror.mode.css.css',
			'libraries.codemirror.mode.htmlmixed.htmlmixed',
			'libraries.codemirror.addon.edit.matchbrackets',
			'libraries.codemirror.addon.edit.closebrackets',
			'libraries.codemirror.addon.edit.closetag',
			'libraries.codemirror.addon.selection.active-line',
			'libraries.codemirror.addon.dialog.dialog',
			'libraries.codemirror.addon.search.searchcursor',
			'libraries.codemirror.addon.search.search',
			'~libraries/js-beautify/beautify-html.min.js',
			'modules.Base.resources.TemplateEditor',
			'modules.EmailTemplates.resources.TemplateAttachments',
		];
		$scriptInstances = $this->checkAndConvertJsScripts($fileNames);
		return array_merge($parentScript, $scriptInstances);
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = [
			'libraries.codemirror.lib.codemirror',
			'libraries.codemirror.addon.dialog.dialog',
			'modules.Base.resources.TemplateEditor',
			'modules.EmailTemplates.Edit',
			'modules.EmailTemplates.TemplateAttachments',
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}
}
