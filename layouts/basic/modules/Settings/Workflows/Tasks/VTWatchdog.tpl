{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/Workflows/Tasks/VTWatchdog.tpl -->
	<div class="row padding-bottom1per">
		<span class="col-md-3">{"LBL_SELECT_ACTION_TYPE"|t:$QUALIFIED_MODULE}</span>
		<div class="col-md-9">
			<select class="chzn-select form-control" name="type" data-validation-engine="validate[required]">
				{foreach from=\App\Fields\Picklist::getPickListValues('notification_type') key=KEY item=ITEM}
					<option {if $TASK_OBJECT->type eq $ITEM}selected{/if} value="{$ITEM}">{$ITEM|t:$TASK_OBJECT->srcWatchdogModule}</option>
				{/foreach}
			</select>
		</div>
	</div>
	<div class="row padding-bottom1per">
		<span class="col-md-3">{"LBL_SELECT_RECIPIENTS"|t:$QUALIFIED_MODULE}</span>
		<div class="col-md-9">
			<select class="chzn-select form-control" name="recipients" data-validation-engine="validate[required]">
				<option {if $TASK_OBJECT->recipients eq 'watchdog'}selected{/if} value="watchdog">
					{"LBL_WATCHING_USERS"|t:$QUALIFIED_MODULE}
				</option>
				<option {if $TASK_OBJECT->recipients eq 'owner'}selected{/if} value="owner">
					{"LBL_OWNER_REKORD"|t:$QUALIFIED_MODULE}
				</option>
				{foreach from=\App\PrivilegeUtil::getMembers() key=GROUP_LABEL item=ALL_GROUP_MEMBERS}
					<optgroup label="{$GROUP_LABEL|t}">
						{foreach from=$ALL_GROUP_MEMBERS key=MEMBER_ID item=MEMBER}
							<option class="{$MEMBER['type']}" value="{$MEMBER_ID}" {if $TASK_OBJECT->recipients eq $MEMBER_ID}selected{/if}>{$MEMBER['name']|t}</option>
						{/foreach}
					</optgroup>
				{/foreach}
			</select>
		</div>
	</div>
	<div class="row padding-bottom1per checkbox">
		<span class="col-md-3">{"LBL_SKIP_CURRENT_USER"|t:$QUALIFIED_MODULE}</span>
		<div class="col-md-9">
			<input name="skipCurrentUser" type="checkbox" value="1" {if $TASK_OBJECT->skipCurrentUser}checked{/if}>
		</div>
	</div>
	<hr/>
	<div class="row">
		{include file='VariablePanel.tpl'|@vtemplate_path SELECTED_MODULE=$SOURCE_MODULE PARSER_TYPE='mail' GRAY=true}
	</div>
	<hr/>
	<div class="row padding-bottom1per">
		<span class="col-md-3">{"LBL_TITLE"|t:$QUALIFIED_MODULE}</span>
		<div class="col-md-9">
			<input name="title" class="form-control" type="text" value="{$TASK_OBJECT->title}">
		</div>
	</div>
	<div class="row padding-bottom1per">
		<span class="col-md-3">{"LBL_MESSAGE"|t:$QUALIFIED_MODULE}</span>
		<div class="col-md-9">
			<textarea class="form-control messageContent" name="message" rows="3">
				{if $TASK_OBJECT->message}
					{$TASK_OBJECT->message}
				{else} 
						 
				{/if} 
			</textarea>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/Workflows/Tasks/VTWatchdog.tpl -->
{/strip}	
