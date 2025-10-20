{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/Workflows/Import.tpl -->
	<div class="row">
		<div class="col-md-12">
			<h3>{"LBL_IMPORT_VIEW"|t:$QUALIFIED_MODULE}</h3>
		</div>
	</div>
	<hr>
	<div class="importTemplateDiv" id="importTemplateContainer">
		{if isset($UPLOAD)}
			{if $UPLOAD eq true}
				<p class="bgMessage bgOK">
					<i class="glyphicon glyphicon-ok-sign"></i> {"LBL_UPLOAD_OK"|t:$QUALIFIED_MODULE} <a href="index.php?module=Workflows&parent=Settings&view=Edit&record={$RECORDID}">{"LBL_GO_TO_TEMPLATE"|t:$QUALIFIED_MODULE}</a>
				</p>
				{foreach from=$MESSAGES['error'] item=msg}
					<p class="bgMessage bgWARNING"><i class="glyphicon glyphicon-info-sign"></i> {$msg}</p>
				{/foreach}
			{elseif $UPLOAD eq false}
				<p class="bgMessage bgERROR">
					{"LBL_UPLOAD_ERROR"|t:$QUALIFIED_MODULE} <a href="{\App\Modules\Settings\Workflows\Models\Module::getDefaultUrl()}">{"LBL_RETURN"|t:$QUALIFIED_MODULE}</a>
				</p>
			{/if}
		{else}
			<form name="ImportWorkflowTemplate" action="index.php" method="post" class="form-horizontal" enctype="multipart/form-data">
				<input type="hidden" name="parent" value="Settings" />
				<input type="hidden" name="module" value="Workflows" />
				<input type="hidden" name="view" value="Import" />
				<input type="hidden" name="upload" value="true" />
				<div class="form-group">
					<label class="col-sm-3 control-label">
						{"LBL_TRIGGER_XML"|t:$QUALIFIED_MODULE}
					</label>
					<div class="col-sm-6 controls">
						<input type="file" name="imported_xml" accept="text/xml" class="form-control" data-validation-engine='validate[required]' id="imported_xml" />
					</div>
				</div>
				<br>
				<div class="pull-right">
					<button class="btn btn-success" type="submit"><strong>{"LBL_UPLOAD_TRIGGER"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
					<a href="{\App\Modules\Settings\Workflows\Models\Module::getDefaultUrl()}" class="btn btn-warning" type="button">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</a>
				</div>
			</form>
		{/if}
	</div>
<!--/layouts/basic/modules/Settings/Workflows/Import.tpl -->
{/strip}
