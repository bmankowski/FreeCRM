{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Events/InviteRow.tpl -->
	{assign var=LABEL value=''}
	{assign var=TITLE value=''}
	<div class="inviteRow" data-crmid="{$INVITIE['crmid']}" data-ivid="{$INVITIE['inviteesid']}" data-email="{$INVITIE['email']}">
		<div class="input-group input-group-sm">
			<span class="input-group-addon inviteIcon">
				{if $INVITIE['crmid'] && isset($INVITIE['metadata'])}
					{assign var=INVITIE_RECORD value=$INVITIE['metadata']}
					{assign var=LABEL value=$INVITIE_RECORD['label']}
					{* Use prepared title from controller, or build from module_label if title not available *}
					{if isset($INVITIE['title'])}
						{assign var=TITLE value=$INVITIE['title']}
					{elseif isset($INVITIE_RECORD['module_label'])}
						{assign var=TITLE value=$INVITIE_RECORD['module_label']|cat:': '|cat:$LABEL|cat:' - '|cat:$INVITIE['email']}
					{/if}
					<span class="userIcon-{$INVITIE_RECORD['setype']}" aria-hidden="true"></span>
				{else}
					{assign var=LABEL value=$INVITIE['email']}
					<span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
				{/if}
			</span>
			<span class="input-group-addon inviteName {if $TITLE}popoverTooltip{/if}" data-content="{$TITLE}" style="width: 100px;">{$LABEL}</span>
			<span class="input-group-addon inviteStatus">
				{assign var=STATUS_LABEL value=\App\Modules\Events\Models\Record::getInvitionStatus($INVITIE['status'])}
				{if $INVITIE['status'] == '1'}
					<span class="glyphicon glyphicon-ok-sign popoverTooltip" data-placement="top" data-content="{$STATUS_LABEL|t:$MODULE} {if $INVITIE['time']}({\App\Fields\DateTimeField::convertToUserFormat($INVITIE['time'])}){/if}" aria-hidden="true"></span>
				{elseif $INVITIE['status'] == '2'}
					<span class="glyphicon glyphicon-minus-sign popoverTooltip" data-placement="top" data-content="{$STATUS_LABEL|t:$MODULE} {if $INVITIE['time']}({\App\Fields\DateTimeField::convertToUserFormat($INVITIE['time'])}){/if}" aria-hidden="true"></span>
				{else}
					{assign var=LABEL value=$INVITIE['email']}
					<span class="glyphicon glyphicon-question-sign popoverTooltip" data-placement="top" data-content="{$STATUS_LABEL|t:$MODULE}" aria-hidden="true"></span>
				{/if}
			</span>
			<span class="input-group-btn">
				<button class="btn btn-default inviteRemove" type="button">
					<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
				</button>
			</span>
		</div>
	</div>
<!--/layouts/basic/modules/Events/InviteRow.tpl -->
{/strip}
