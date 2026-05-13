{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/PDF/Step4.tpl -->
	<div class="pdfTemplateContents">
		<form name="EditPdfTemplate" action="index.php" method="post" id="pdf_step4" class="form-horizontal">
			<input type="hidden" name="module" value="Template">
			<input type="hidden" name="view" value="Edit">
			<input type="hidden" name="mode" value="Step5" />
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" class="step" value="4" />
			<input type="hidden" name="record" value="{$RECORDID}" />
			<input type="hidden" name="conditions" id="advanced_filter" value='' />

			{include file='AdvanceFilterExpressions.tpl'|@vtemplate_path}
			<br>
			<div class="pull-right">
				<button class="btn btn-danger backStep" type="button"><strong>{"LBL_BACK"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-success" type="submit"><strong>{"LBL_NEXT"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-warning cancelLink" type="reset">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Settings/PDF/Step4.tpl -->
{/strip}
