{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/PDF/Step3.tpl -->
	<div class="pdfTemplateContents">
		<form name="EditPdfTemplate" action="index.php" method="post" id="pdf_step3" class="form-horizontal">
			<input type="hidden" name="module" value="Template">
			<input type="hidden" name="view" value="Edit">
			<input type="hidden" name="mode" value="Step4" />
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" name="step" class="step" value="3" />
			<input type="hidden" name="record" value="{$RECORDID}" />
			<input type="hidden" name="module_name" value="{$PDF_MODEL->get('module_name')}" />
			<div class="padding1per stepBorder">
				<label>
					<strong>{'LBL_STEP_N'|t:$QUALIFIED_MODULE:3}: {"LBL_DOCUMENT_CONTENT_DETAILS"|t:$QUALIFIED_MODULE}</strong>
				</label>
				<br>
				<div class="row">
					{include file='layouts/basic/modules/Base/VariablePanel.tpl' SELECTED_MODULE=$SELECTED_MODULE PARSER_TYPE='pdf' QUALIFIED_SETTINGS_MODULE=$QUALIFIED_MODULE VARIABLE_PANEL_DYNAMIC_ALIASES=$VARIABLE_PANEL_DYNAMIC_ALIASES}
				</div>
				<div class="pdfEditorToolbar text-right">
					<input type="hidden" class="js-dynamic-elements-json" value="{$DYNAMIC_ELEMENTS_JSON}">
					<button class="btn btn-info btn-sm js-format-html" type="button">
						<span class="glyphicon glyphicon-indent-left"></span>&nbsp;{"LBL_FORMAT_HTML"|t:$QUALIFIED_MODULE}
					</button>
					<button class="btn btn-primary btn-sm js-preview-html" type="button">
						<span class="glyphicon glyphicon-eye-open"></span>&nbsp;{"LBL_PREVIEW_HTML"|t:$QUALIFIED_MODULE}
					</button>
					<button class="btn btn-default btn-sm js-toggle-ai-help" type="button">
						<span class="glyphicon glyphicon-info-sign"></span>&nbsp;{*"LBL_TEMPLATE_AI_HELP"|t:$QUALIFIED_MODULE*}
					</button>
				</div>
				<div class="pdfTemplateAiHelp hide">
					<textarea class="form-control" rows="3" readonly>{"LBL_TEMPLATE_AI_HELP_TEXT"|t:$QUALIFIED_MODULE}</textarea>
				</div>
				<div class="form-group">
					<label class="col-sm-12">
						{"LBL_DOCUMENT_HEADER"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-12 controls">
						<textarea class="form-control" name="header_content" id="header_content">{$PDF_MODEL->get('header_content')}</textarea>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-12">
						{"LBL_DOCUMENT_BODY"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-12 controls">
						<textarea class="form-control" name="body_content" id="body_content">{$PDF_MODEL->get('body_content')}</textarea>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-12">
						{"LBL_DOCUMENT_FOOTER"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-12 controls">
						<textarea class="form-control" name="footer_content" id="footer_content">{$PDF_MODEL->get('footer_content')}</textarea>
					</div>
				</div>
			</div>
			<br>
			<div class="pull-right">
				<button class="btn btn-danger backStep" type="button"><strong>{"LBL_BACK"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-default js-save-step3-only" type="button"><strong>{"LBL_SAVE"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-success" type="submit"><strong>{"LBL_NEXT"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-warning cancelLink" type="reset">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Settings/PDF/Step3.tpl -->
{/strip}
