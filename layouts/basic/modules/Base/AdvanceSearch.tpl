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
<!-- layouts/basic/modules/Base/AdvanceSearch.tpl -->
    <div id="advanceSearchContainer" class="modal fade" tabindex="-1">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<div class="row no-margin">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" aria-hidden="true">&times;</button>
						<div class="col-md-5 pushDown">
							<strong class="pull-right">{"LBL_SEARCH_IN"|t:$MODULE}</strong>
						</div>
						<div class="col-md-6">
							<select class="chzn-select form-control" id="searchModuleList" title="{"LBL_SELECT_MODULE"|t}" data-placeholder="{"LBL_SELECT_MODULE"|t}">
								<option></option>
								{foreach key=MODULE_NAME item=fieldObject from=$SEARCHABLE_MODULES}
									<option value="{$MODULE_NAME}" {if $MODULE_NAME eq $SOURCE_MODULE}selected="selected"{/if}>{$MODULE_NAME|t:$MODULE_NAME}</option>
								{/foreach}
							</select>
						</div>
					</div>
				</div>
				<div class="modal-body">
					<div class="filterElements" id="searchContainer">
						<form name="advanceFilterForm">
							{if $SOURCE_MODULE eq 'Home'}
								<div class="textAlignCenter">{"LBL_PLEASE_SELECT_MODULE"|t:$MODULE}</div>
							{else}
								<input type="hidden" name="labelFields" data-value='{\App\Json::encode($SOURCE_MODULE_MODEL->getNameFields())}' />
								{include file='AdvanceFilter.tpl'|@vtemplate_path}
							{/if}	
						</form>
					</div>
				</div>

				<div class="actions modal-footer">
					<a class="cancelLink pull-right btn btn-warning" type="reset" id="advanceSearchCancel" data-dismiss="modal">{"LBL_CANCEL"|t:$MODULE}</a>
					<button class="btn btn-info pull-right" id="advanceSearchButton" {if $SOURCE_MODULE eq 'Home'} disabled="" {/if}  type="submit"><strong>{"LBL_SEARCH"|t:$MODULE}</strong></button>
					{if $SAVE_FILTER_PERMITTED}
						<button class="btn hide btn-success pull-right" {if $SOURCE_MODULE eq 'Home'} disabled="" {/if} id="advanceSave">
							<strong>{"LBL_SAVE_FILTER"|t:$MODULE}</strong>
						</button>
						{if \App\Modules\Users\Models\Privileges::isPermitted($SOURCE_MODULE, 'CreateCustomFilter')}
							<button class="btn btn-success pull-right" {if $SOURCE_MODULE eq 'Home'} disabled="" {/if} id="advanceIntiateSave">
								<strong>{"LBL_SAVE_AS_FILTER"|t:$MODULE}</strong>
							</button>
						{/if}
						<div class="col-xs-3 pull-right">
							<input class="zeroOpacity pull-left form-control" type="text" title="{"LBL_FILTER_NAME"|t}" value="" name="viewname" placeholder="{"LBL_FILTER_NAME"|t}"/>
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Base/AdvanceSearch.tpl -->
{/strip}
