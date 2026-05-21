{*<!-- FreeCRM - shared template editor toolbar (preview + help) -->*}
{strip}
	<!-- layouts/basic/modules/Base/TemplateEditorToolbar.tpl -->
	{assign var=TE_LAYOUT value=$TEMPLATE_EDITOR_LAYOUT|default:'grid'}
	{if !empty($DYNAMIC_ELEMENTS_JSON)}
		<input type="hidden" class="js-dynamic-elements-json" value="{$DYNAMIC_ELEMENTS_JSON}">
	{/if}
	{if $TE_LAYOUT eq 'form'}
		<div class="form-group templateEditorToolbarFormRow">
			<label class="control-label col-md-3"></label>
			<div class="controls col-md-8">
				<div class="templateEditorToolbar text-right marginBottom10px">
					<button class="btn btn-primary btn-sm js-template-editor-preview" type="button">
						<span class="glyphicon glyphicon-eye-open"></span>&nbsp;{"LBL_PREVIEW_HTML"|t:'Vtiger'}
					</button>
					<button class="btn btn-default btn-sm js-template-editor-toggle-help" type="button">
						<span class="glyphicon glyphicon-info-sign"></span>&nbsp;{"LBL_TEMPLATE_EDITOR_HELP"|t:'Vtiger'}
					</button>
				</div>
				<div class="templateEditorHelp hide marginBottom10px">
					{assign var=templateEditorHelpHtml value='LBL_TEMPLATE_EDITOR_HELP_TEXT'|t:'Vtiger'}
					<div class="well templateEditorHelpText">{$templateEditorHelpHtml nofilter}</div>
				</div>
				<div class="templateEditorPreview hide marginBottom10px">
					<iframe class="templateEditorPreviewFrame" sandbox="" title="{"LBL_PREVIEW_HTML"|t:'Vtiger'}"
						style="background:#fff;border:1px solid #ccc;height:420px;width:100%;"></iframe>
				</div>
			</div>
		</div>
	{else}
		<div class="templateEditorToolbar text-right marginBottom10px">
			<button class="btn btn-primary btn-sm js-template-editor-preview" type="button">
				<span class="glyphicon glyphicon-eye-open"></span>&nbsp;{"LBL_PREVIEW_HTML"|t:'Vtiger'}
			</button>
			<button class="btn btn-default btn-sm js-template-editor-toggle-help" type="button">
				<span class="glyphicon glyphicon-info-sign"></span>&nbsp;{"LBL_TEMPLATE_EDITOR_HELP"|t:'Vtiger'}
			</button>
		</div>
		<div class="templateEditorHelp hide marginBottom10px">
			{assign var=templateEditorHelpHtml value='LBL_TEMPLATE_EDITOR_HELP_TEXT'|t:'Vtiger'}
			<div class="well templateEditorHelpText">{$templateEditorHelpHtml nofilter}</div>
		</div>
		<div class="templateEditorPreview hide marginBottom10px">
			<iframe class="templateEditorPreviewFrame" sandbox="" title="{"LBL_PREVIEW_HTML"|t:'Vtiger'}"
				style="background:#fff;border:1px solid #ccc;height:420px;width:100%;"></iframe>
		</div>
	{/if}
	<!--/layouts/basic/modules/Base/TemplateEditorToolbar.tpl -->
{/strip}
