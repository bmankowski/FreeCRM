{strip}
<div class="panel panel-default mailPreferencePanel" id="mailPreferencePanel">
	<div class="panel-heading"><strong>{"LBL_MAILBOX"|t:"Mail"}</strong></div>
	<div class="panel-body">
		<p class="text-muted">{"LBL_MAILBOX_HELP"|t:"Mail"}</p>
		{if $MAIL_ACCOUNT.id && $MAIL_ACCOUNT.active|default:0 != 1}
			<p class="alert alert-warning">{"LBL_MAILBOX_PENDING_ACTIVATION"|t:"Mail"}</p>
		{/if}
		<form id="PersonalMailboxForm">
			<input type="hidden" name="owner_user_id" value="{$RECORD_ID}">
			{include file='MailboxFields.tpl'|@vtemplate_path:'Mail'}
		</form>
	</div>
</div>
{/strip}
