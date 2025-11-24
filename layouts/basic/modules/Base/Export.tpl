{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce.com
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Base/Export.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
	<div class="bodyContents">
		<div class="mainContainer">
			<div class="contentsDiv">
				
				{* Header with breadcrumbs *}
				<div class="widget_header row marginBottom10px">
					<div class="col-xs-12">
						{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
					</div>
				</div>

				{* Export form *}
				<div class="row">
					<div class="col-md-8">
						<form id="exportForm" class="form-horizontal" method="post" action="index.php">
							<input type="hidden" name="module" value="{$MODULE}" />
							<input type="hidden" name="source_module" value="{$SOURCE_MODULE}" />
							<input type="hidden" name="action" value="ExportData" />
							<input type="hidden" name="viewname" value="{$VIEWID}" />
							<input type="hidden" name="selected_ids" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($SELECTED_IDS))}">
							<input type="hidden" name="excluded_ids" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($EXCLUDED_IDS))}">
							<input type="hidden" id="page" name="page" value="{$PAGE}" />
							{if isset($SEARCH_KEY)}<input type="hidden" name="search_key" value="{$SEARCH_KEY}" />{/if}
							{if isset($OPERATOR)}<input type="hidden" name="operator" value="{$OPERATOR}" />{/if}
							{if isset($ALPHABET_VALUE)}<input type="hidden" name="search_value" value="{$ALPHABET_VALUE}" />{/if}
							{if isset($SEARCH_PARAMS)}<input type="hidden" name="search_params" value='{\App\Utils\Json::encode($SEARCH_PARAMS)}' />{/if}

							<div class="well exportContents">
								<div class="radio">
									<label title="{"LBL_EXPORT_SELECTED_RECORDS"|t}">
										<input type="radio" name="mode" id="optionsRadios1" value="ExportSelectedRecords" {if !empty($SELECTED_IDS)} checked="checked" {else} disabled="disabled"{/if}>
										{"LBL_EXPORT_SELECTED_RECORDS"|t:$MODULE}
										{if empty($SELECTED_IDS)}&nbsp; - <span class="redColor">{"LBL_NO_RECORD_SELECTED"|t:$MODULE}</span>{/if}
									</label>
								</div>
								<div class="radio">
									<label title="{"LBL_EXPORT_DATA_IN_CURRENT_PAGE"|t:$MODULE}">
										<input type="radio" name="mode" id="optionsRadios2" value="ExportCurrentPage">
										{"LBL_EXPORT_DATA_IN_CURRENT_PAGE"|t:$MODULE}
									</label>
								</div>
								<div class="radio">
									<label title="{"LBL_EXPORT_ALL_DATA"|t:$MODULE}">
										<input type="radio" name="mode" id="optionsRadios3" value="ExportAllData" {if empty($SELECTED_IDS)} checked="checked" {/if}>
										{"LBL_EXPORT_ALL_DATA"|t:$MODULE}
									</label>
								</div>
								<br>
								<hr>
								<div class="row">
									<div class="col-md-6">
										<label class="">{"LBL_EXPORT_TYPE"|t:$MODULE}</label>
										<div class="">
											<select class="select2" id="exportType" name="export_type">
												{foreach from=$EXPORT_TYPE item=TYPE key=LABEL}
													<option value="{$TYPE}">{{$LABEL}|t:$MODULE}</option>
												{/foreach}
											</select>
										</div>
									</div>
									{if $XML_TPL_LIST}
										<div class="col-md-6 hide xml-tpl">
											<label class="">{"LBL_XML_EXPORT_TPL"|t:$MODULE}</label>
											<div class="">
												<select class="select2" id="xmlExportType" name="xmlExportType">
													{foreach from=$XML_TPL_LIST item=item key=key}
														<option value="{$item}">{{$item}|t:$MODULE}</option>
													{/foreach}
												</select>
											</div>
										</div>
									{/if}				
								</div>
								<div class="row csv-separator-options" style="margin-top: 15px;">
									<div class="col-md-12">
										<label class="">{"LBL_CSV_SEPARATOR"|t:$MODULE}</label>
										<div class="">
											<div class="radio">
												<label>
													<input type="radio" name="csv_separator" value="comma" checked="checked">
													{"LBL_CSV_SEPARATOR_COMMA"|t:$MODULE}
												</label>
											</div>
											<div class="radio">
												<label>
													<input type="radio" name="csv_separator" value="semicolon">
													{"LBL_CSV_SEPARATOR_SEMICOLON"|t:$MODULE}
												</label>
											</div>
											<div class="radio">
												<label>
													<input type="radio" name="csv_separator" value="tab">
													{"LBL_CSV_SEPARATOR_TAB"|t:$MODULE}
												</label>
											</div>
											<div class="radio">
												<label>
													<input type="radio" name="csv_separator" value="custom" id="csvSeparatorCustom">
													{"LBL_CSV_SEPARATOR_CUSTOM"|t:$MODULE}
												</label>
											</div>
											<div class="form-group" id="csvSeparatorCustomInput" style="margin-left: 20px; margin-top: 10px; display: none;">
												<label for="csvSeparatorCustomValue" class="control-label">{"LBL_CSV_SEPARATOR_CUSTOM_VALUE"|t:$MODULE}</label>
												<input type="text" name="csv_separator_custom" id="csvSeparatorCustomValue" maxlength="1" class="form-control" style="width: 100px; display: inline-block;" placeholder="">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="form-group">
								<button class="btn btn-success saveButton" type="submit"><strong>{"LBL_EXPORT"|t:$MODULE}</strong></button>
								<button class="btn btn-warning" type="reset" onclick='window.history.back()'>{"LBL_CANCEL"|t:$MODULE}</button>
							</div>
						</form>
					</div>
				</div>

			</div> <!-- close contentsDiv -->
		</div> <!-- close mainContainer -->
	</div> <!-- close bodyContents -->
{/block}
<!--/layouts/basic/modules/Base/Export.tpl -->
{/strip}
