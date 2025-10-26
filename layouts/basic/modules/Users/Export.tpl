{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Users/Export.tpl -->
    <div style="padding-left: 15px;">
        <form id="exportForm" class="form-horizontal row" method="post" action="index.php">
            <input type="hidden" name="module" value="{$MODULE}" />
            <input type="hidden" name="source_module" value="{$SOURCE_MODULE}" />
            <input type="hidden" name="action" value="ExportData" />
            <input type="hidden" name="viewname" value="{$VIEWID}" />
            <input type="hidden" name="selected_ids" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode($SELECTED_IDS))}">
            <input type="hidden" name="excluded_ids" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode($EXCLUDED_IDS))}">
            <input type="hidden" id="page" name="page" value="{$PAGE}" />
            <input type="hidden" name="search_key" value= "{$SEARCH_KEY}" />
            <input type="hidden" name="operator" value="{$OPERATOR}" />
            <input type="hidden" name="search_value" value="{$ALPHABET_VALUE}" />
            <input type="hidden" name="search_params" value='{\App\Json::encode($SEARCH_PARAMS)}' />

            <div class="">
                <div class="span">&nbsp;</div>
                <div class="col-md-10">
                    <h4>{"LBL_EXPORT_RECORDS"|t:$MODULE}</h4>
			<div class="alert alert-warning">
				<a href="#" class="close" data-dismiss="alert">&times;</a>
				{"LBL_INFO_USER_EXPORT_RECORDS"|t:$MODULE}
			</div>
                    <div class="well exportContents marginLeftZero">
                        <fieldset>
                            <legend class="hide">{"LBL_EXPORT_RECORDS"|t:$MODULE}</legend>
							<div class="row">
                                    <div class="col-md-6 textAlignRight row">
                                        <div class="col-md-8">{"LBL_EXPORT_SELECTED_RECORDS"|t:$MODULE}&nbsp;</div>
										<div class="col-md-3">
											<input type="radio" name="mode" title="{"LBL_EXPORT_SELECTED_RECORDS"|t}" value="ExportSelectedRecords" {if !empty($SELECTED_IDS)} checked="checked" {else} disabled="disabled"{/if}/>
										</div>
                                    </div>
					<div class="col-md-6">
					{if empty($SELECTED_IDS)}&nbsp; <span class="redColor">{"LBL_NO_RECORD_SELECTED"|t:$MODULE}</span>{/if}
					</div>
				</div>
				<div class="row">
					<div class="col-md-6 textAlignRight row">
					<div class="col-md-8">{"LBL_EXPORT_ALL_DATA"|t:$MODULE}&nbsp;</div>
					<div class="col-md-3"><input type="radio"  name="mode" value="ExportAllData" title="{"LBL_EXPORT_ALL_DATA"|t:$MODULE}" {if empty($SELECTED_IDS)} checked="checked" {/if} /></div>
					</div>
				</div>
                        </fieldset>
                    </div>
                    <br>
                    <div class="textAlignCenter">
                        <button class="btn btn-success" type="submit"><strong>{$MODULE|t:$MODULE}&nbsp;{$SOURCE_MODULE|t:$SOURCE_MODULE}</strong></button>
						&nbsp;&nbsp;
                        <button class="btn btn-warning" type="reset" onclick='window.history.back()'>{"LBL_CANCEL"|t:$MODULE}</button>
                    </div>
                </div>
            </div>
	</div>
</form>
</div>
<!--/layouts/basic/modules/Users/Export.tpl -->
{/strip}
