{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
	<!-- layouts/basic/modules/Settings/PDFDynamicElements/Edit.tpl -->
	{extends file="MainLayout.tpl"|@vtemplate_path}

	{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				<div class="widget_header row marginBottom10px">
					<div class="col-xs-12">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
						<h3>{if $RECORD_ID}{'LBL_EDIT_DYNAMIC_ELEMENT'|t:$QUALIFIED_MODULE}{else}{'LBL_ADD_DYNAMIC_ELEMENT'|t:$QUALIFIED_MODULE}{/if}</h3>
					</div>
				</div>
				<div class="editViewContainer">
					<form name="EditPDFDynamicElement" id="EditView" class="form-horizontal validateForm" method="post" action="index.php">
						<input type="hidden" name="module" value="TemplateElements">
						
						<input type="hidden" name="action" value="Save">
						<input type="hidden" name="record" value="{$RECORD_ID}">
						<input type="hidden" name="returnToList" value="1">
						<div class="form-group">
							<label class="control-label col-md-3">{'LBL_LABEL'|t:$QUALIFIED_MODULE} <span class="redColor">*</span></label>
							<div class="controls col-md-8">
								<input class="form-control" type="text" name="label" value="{$RECORD_MODEL->get('label')|escape}" data-validation-engine="validate[required]">
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">{'LBL_CODE'|t:$QUALIFIED_MODULE} <span class="redColor">*</span></label>
							<div class="controls col-md-8">
								<input class="form-control" type="text" name="code" value="{$RECORD_MODEL->get('code')|escape}" data-validation-engine="validate[required]" {if $RECORD_ID}readonly{/if}>
								<p class="help-block">{'LBL_CODE_DESCRIPTION'|t:$QUALIFIED_MODULE}</p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">{'LBL_TYPE'|t:$QUALIFIED_MODULE}</label>
							<div class="controls col-md-8">
								<select class="select2 form-control" name="type">
									{foreach item=TYPE_GROUP from=$TYPE_SELECT_GROUPS}
										{if $TYPE_GROUP.groupLabel neq ''}
											<optgroup label="{$TYPE_GROUP.groupLabel|t:$QUALIFIED_MODULE}">
												{foreach item=TYPE_LABEL key=TYPE from=$TYPE_GROUP.types}
													<option value="{$TYPE}" {if $RECORD_MODEL->get('type') eq $TYPE}selected{/if}>{$TYPE_LABEL|t:$QUALIFIED_MODULE}</option>
												{/foreach}
											</optgroup>
										{else}
											{foreach item=TYPE_LABEL key=TYPE from=$TYPE_GROUP.types}
												<option value="{$TYPE}" {if $RECORD_MODEL->get('type') eq $TYPE}selected{/if}>{$TYPE_LABEL|t:$QUALIFIED_MODULE}</option>
											{/foreach}
										{/if}
									{/foreach}
								</select>
								<p class="help-block">{'LBL_TYPE_DOCUMENT_LAYOUT_HINT'|t:$QUALIFIED_MODULE}</p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">{'LBL_MODULE'|t:$QUALIFIED_MODULE}</label>
							<div class="controls col-md-8">
								<input class="form-control" type="text" name="module_name" value="{$RECORD_MODEL->get('module_name')|escape}">
								<p class="help-block">{'LBL_EMPTY_IS_GLOBAL'|t:$QUALIFIED_MODULE}</p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">{'LBL_LANGUAGE'|t:$QUALIFIED_MODULE}</label>
							<div class="controls col-md-8">
								<select class="select2 form-control" name="language">
									<option value="">{'LBL_ALL_LANGUAGES'|t:$QUALIFIED_MODULE}</option>
									{foreach item=LANGUAGE_LABEL key=LANGUAGE from=$LANGUAGES}
										<option value="{$LANGUAGE}" {if $RECORD_MODEL->get('language') eq $LANGUAGE}selected{/if}>{$LANGUAGE_LABEL}</option>
									{/foreach}
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">{'LBL_STATUS'|t:$QUALIFIED_MODULE}</label>
							<div class="controls col-md-8">
								<label class="checkbox">
									<input type="checkbox" name="status" value="1" {if !$RECORD_ID || $RECORD_MODEL->get('status') eq 1}checked{/if}> {'LBL_ACTIVE'|t:$QUALIFIED_MODULE}
								</label>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">{'LBL_SEQUENCE'|t:$QUALIFIED_MODULE}</label>
							<div class="controls col-md-8">
								<input class="form-control" type="text" name="sequence" value="{$RECORD_MODEL->get('sequence')|escape}" data-validation-engine="validate[custom[integer]]">
							</div>
						</div>
						<div class="form-group">
							<div class="controls col-md-11 col-md-offset-1">
								<label class="control-label text-left">
									<strong>{'LBL_VARIABLE_PANEL_SECTION'|t:$QUALIFIED_MODULE}</strong>
								</label>
								{if empty($SELECTED_MODULE)}
									<p class="help-block">{'LBL_VARIABLE_PANEL_NEED_MODULE'|t:$QUALIFIED_MODULE}</p>
								{/if}
								<div class="row marginTop10px">
									{include file='layouts/basic/modules/Base/VariablePanel.tpl' SELECTED_MODULE=$SELECTED_MODULE PARSER_TYPE='pdf' QUALIFIED_SETTINGS_MODULE=$QUALIFIED_SETTINGS_MODULE VARIABLE_PANEL_DYNAMIC_ALIASES=$VARIABLE_PANEL_DYNAMIC_ALIASES}
								</div>
							</div>
						</div>
						<input type="hidden" id="documentLayoutTypeValue" value="PLL_DOCUMENT_LAYOUT">
						<div class="form-group js-dynamic-fragment-editor{if $RECORD_MODEL->get('type') eq 'PLL_DOCUMENT_LAYOUT'} hide{/if}">
							<label class="control-label col-md-3">{'LBL_CONTENT'|t:$QUALIFIED_MODULE} <span class="redColor">*</span></label>
							<div class="controls col-md-8">
								<textarea class="form-control js-dynamic-content" id="dynamicElementContent" name="content" rows="16" {if $RECORD_MODEL->get('type') neq 'PLL_DOCUMENT_LAYOUT'}data-validation-engine="validate[required]"{/if}>{$RECORD_MODEL->get('content')|escape}</textarea>
							</div>
						</div>
						<div class="form-group js-dynamic-layout-editor{if $RECORD_MODEL->get('type') neq 'PLL_DOCUMENT_LAYOUT'} hide{/if}">
							<label class="control-label col-md-3">{'LBL_LAYOUT_HEADER'|t:$QUALIFIED_MODULE}</label>
							<div class="controls col-md-8">
								<textarea class="form-control js-dynamic-layout-header" id="dynamicElementLayoutHeader" name="layout_header" rows="6">{$RECORD_MODEL->get('layout_header')|escape}</textarea>
							</div>
						</div>
						<div class="form-group js-dynamic-layout-editor{if $RECORD_MODEL->get('type') neq 'PLL_DOCUMENT_LAYOUT'} hide{/if}">
							<label class="control-label col-md-3">{'LBL_LAYOUT_BODY'|t:$QUALIFIED_MODULE} <span class="redColor">*</span></label>
							<div class="controls col-md-8">
								<textarea class="form-control js-dynamic-layout-body" id="dynamicElementLayoutBody" name="layout_body" rows="16" {if $RECORD_MODEL->get('type') eq 'PLL_DOCUMENT_LAYOUT'}data-validation-engine="validate[required]"{/if}>{$RECORD_MODEL->get('layout_body')|escape}</textarea>
							</div>
						</div>
						<div class="form-group js-dynamic-layout-editor{if $RECORD_MODEL->get('type') neq 'PLL_DOCUMENT_LAYOUT'} hide{/if}">
							<label class="control-label col-md-3">{'LBL_LAYOUT_FOOTER'|t:$QUALIFIED_MODULE}</label>
							<div class="controls col-md-8">
								<textarea class="form-control js-dynamic-layout-footer" id="dynamicElementLayoutFooter" name="layout_footer" rows="6">{$RECORD_MODEL->get('layout_footer')|escape}</textarea>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">{'LBL_DESCRIPTION'|t:$QUALIFIED_MODULE}</label>
							<div class="controls col-md-8">
								<textarea class="form-control" name="description" rows="3">{$RECORD_MODEL->get('description')|escape}</textarea>
							</div>
						</div>
						<div class="row">
							<div class="col-md-11">
								<span class="pull-right">
									<button class="btn btn-success" type="submit"><span class="glyphicon glyphicon-ok"></span>&nbsp;<strong>{'LBL_SAVE_AND_CLOSE'|t:$QUALIFIED_MODULE}</strong></button>
									<a class="cancelLink btn btn-warning" href="index.php?module=TemplateElements&view=ListView"><span class="glyphicon glyphicon-remove"></span>&nbsp;{'LBL_CANCEL'|t:$QUALIFIED_MODULE}</a>
								</span>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	{/block}
	<!--/layouts/basic/modules/Settings/PDFDynamicElements/Edit.tpl -->
{/strip}
