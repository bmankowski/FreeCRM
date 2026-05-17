{strip}
<div class="form-group">
	<div class="col-md-6 controls">
		<select class="select2 form-control" multiple="true" name="template_members[]" data-placeholder="{"LBL_ADD_USERS_ROLES"|t:"Settings:Groups"}">
			{assign var=TEMPLATE_MEMBERS value=','|explode:$RECORD->get('template_members')}
			{foreach from=$ALL_GROUP_MEMBERS key=GROUP_LABEL item=ALL_GROUP_MEMBERS_LIST}
				<optgroup label="{$GROUP_LABEL|t:$MODULE}">
					{foreach from=$ALL_GROUP_MEMBERS_LIST item=MEMBER}
						<option value="{$MEMBER->get('id')}" data-member-type="{$GROUP_LABEL}" {if in_array($MEMBER->get('id'), $TEMPLATE_MEMBERS)}selected="true"{/if}>{$MEMBER->get('name')|t:$MODULE}</option>
					{/foreach}
				</optgroup>
			{/foreach}
		</select>
	</div>
</div>
{/strip}
