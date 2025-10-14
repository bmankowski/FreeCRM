{*<!--
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
-->*}
{strip}
<!-- layouts/basic/modules/Settings/MappedFields/Import.tpl -->
	<div class="modal-header">
		<button class="close" data-dismiss="modal" title="{"LBL_CLOSE"|t}">x</button>
		<h3 class="modal-title">{"LBL_IMPORT_VIEW"|t:$QUALIFIED_MODULE}</h3>
	</div>
	<form name="importTemplate" action="index.php" method="post" class="form-horizontal" enctype="multipart/form-data">
		<div class="modal-body">
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" name="module" value="MappedFields" />
			<input type="hidden" name="action" value="SaveAjax" />
			<input type="hidden" name="mode" value="import" />
			<div class="form-group">
				<label class="col-sm-3 control-label">
					{"LBL_TEMPLATE_XML"|t:$QUALIFIED_MODULE}
				</label>
				<div class="col-sm-8 controls">
					<input type="file" name="imported_xml" accept="text/xml" class="form-control" data-validation-engine='validate[required]' id="imported_xml" />
				</div>
			</div>

		</div>
		<div class="modal-footer">
			<div class="pull-right">
				<button class="btn btn-success" type="submit"><strong>{"LBL_UPLOAD_TEMPLATE"|t:$QUALIFIED_MODULE}</strong></button>&nbsp;&nbsp;
				<button type="button" class="btn btn-warning dismiss" data-dismiss="modal">{"LBL_CLOSE"|t:$MODULE_NAME}</button>
			</div>
		</div>
	</form>		
<!--/layouts/basic/modules/Settings/MappedFields/Import.tpl -->
{/strip}
