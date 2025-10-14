{*<!--
/*+***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 *************************************************************************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/HideBlocks/Conditions.tpl -->
<div class="targetFieldsTableContainer">
	<div class="widget_header row">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{"LBL_HIDEBLOCKS_DESCRIPTION"|t:$QUALIFIED_MODULE}
		</div>
	</div>
	{if $MANDATORY_FIELDS}
		<div class="alert alert-warning">
			{"LBL_MANDATORY_FIELDS_EXIST"|t:$QUALIFIED_MODULE}
		</div>
		<br>	
		<div class="pull-right">
			<a class="btn btn-danger" type="reset" onclick="javascript:window.history.back();">{"LBL_BACK"|t:$MODULE}</a>
		</div>
		<div class="clearfix"></div>
	{else}
		<form method="post" action="index.php">
			<input type="hidden" name="module" value="{$MODULE}"/>
			<input type="hidden" name="parent" value="Settings"/>
			<input type="hidden" name="action" value="Save" />
			<input type="hidden" name="record" value="{$RECORD_ID}"/>
			<input type="hidden" name="blockid" value="{$BLOCKID}"/>
			<input type="hidden" name="enabled" value="{$ENABLED}"/>
			<input type="hidden" name="views" value="{$VIEWS}"/>
			<input type="hidden" name="conditions" class="advanced_filter" value="{$ENABLED}"/>
			<div class="listViewEntriesDiv contents-bottomscroll" style="overflow-x: visible !important;">
				<div class="bottomscroll-div">
					{include file='AdvanceFilter.tpl'|@vtemplate_path RECORD_STRUCTURE=$RECORD_STRUCTURE}
				</div>
			</div>
			<br>	
			<div class="">
				<div class="pull-right">
					<a class="saveLink btn btn-success" ><strong>{"LBL_SAVE"|t:$MODULE}</strong></a>
					<a class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();">{"LBL_BACK"|t:$MODULE}</a>
				</div>
				<div class="clearfix"></div>
			</div>
		</form>	
	{/if}
</div>
<!--/layouts/basic/modules/Settings/HideBlocks/Conditions.tpl -->
{/strip}
