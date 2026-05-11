{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/PDF/ListViewHeader.tpl -->
		<div class="listViewTopMenuDiv">
			<div class="row widget_header">
				<div class="col-xs-12">
					{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					{"LBL_PDF_DESCRIPTION"|t:$QUALIFIED_MODULE}
				</div>
			</div>
			{if $MPDF_LIBRARY_CHECK}
				<div class="alert alert-danger" role="alert">
					<div>
						<h4>{'ERR_NO_REQUIRED_LIBRARY'|t:'Settings:Vtiger','mPDF'}</h4>
					</div>
				</div>
				<hr>
			{/if}
			<div class="row">
				<div class="col-md-4 btn-toolbar">
					<button class="btn btn-default addButton" id="addButton" data-url="{$CREATE_RECORD_URL}">
						<i class="glyphicon glyphicon-plus"></i>&nbsp;
						<strong>{"LBL_NEW"|t:$QUALIFIED_MODULE} {"LBL_PDF_TEMPLATE"|t:$QUALIFIED_MODULE}</strong>
					</button>
					<button class="btn btn-default importButton" id="importButton" data-url="{$IMPORT_VIEW_URL}" title="{"LBL_IMPORT_TEMPLATE"|t:$QUALIFIED_MODULE}">
						<i class="glyphicon glyphicon-import"></i>
					</button>
				</div>
				<div class="col-md-4 btn-toolbar">
					<select class="chzn-select" id="moduleFilter" >
						<option value="">{"LBL_ALL"|t:$QUALIFIED_MODULE}</option>
						{foreach item=MODULE_MODEL key=TAB_ID from=$SUPPORTED_MODULE_MODELS}
							<option {if $SOURCE_MODULE eq $MODULE_MODEL->getName()} selected="" {/if} value="{$MODULE_MODEL->getName()}">
								{if $MODULE_MODEL->getName() eq 'Calendar'}
									{'LBL_TASK'|t:$MODULE_MODEL->getName()}
								{else}
									{$MODULE_MODEL->getName()|t:$MODULE_MODEL->getName()}
								{/if}
							</option>
						{/foreach}
					</select>
				</div>
				<div class="col-md-4 btn-toolbar">
					{include file='ListViewActions.tpl'|@vtemplate_path}
				</div>
			</div>
		<div class="listViewContentDiv" id="listViewContents">
<!--/layouts/basic/modules/Settings/PDF/ListViewHeader.tpl -->
		{/strip}
