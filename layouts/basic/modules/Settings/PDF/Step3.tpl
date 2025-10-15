{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/PDF/Step3.tpl -->
	<div class="pdfTemplateContents">
		<form name="EditPdfTemplate" action="index.php" method="post" id="pdf_step3" class="form-horizontal">
			<input type="hidden" name="module" value="PDF">
			<input type="hidden" name="view" value="Edit">
			<input type="hidden" name="mode" value="Step4" />
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" class="step" value="3" />
			<input type="hidden" name="record" value="{$RECORDID}" />
			<input type="hidden" name="module_name" value="{$PDF_MODEL->get('module_name')}" />
			<div class="padding1per stepBorder">
				<label>
					<strong>{'LBL_STEP_N'|t:$QUALIFIED_MODULE, 3}: {"LBL_HEADER_DETAILS"|t:$QUALIFIED_MODULE}</strong>
				</label>
				<br>
				<div class="row">
					{include file='layouts/basic/modules/Vtiger/VariablePanel.tpl' SELECTED_MODULE=$SELECTED_MODULE PARSER_TYPE='pdf'}
				</div>
				<div class="form-group">
					<div class="col-sm-12 controls">
						<textarea class="form-control" name="header_content" id="header_content">{$PDF_MODEL->get('header_content')}</textarea>
					</div>
				</div>
			</div>
			<br>
			<div class="pull-right">
				<button class="btn btn-danger backStep" type="button"><strong>{"LBL_BACK"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-success" type="submit"><strong>{"LBL_NEXT"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-warning cancelLink" type="reset">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Settings/PDF/Step3.tpl -->
{/strip}
