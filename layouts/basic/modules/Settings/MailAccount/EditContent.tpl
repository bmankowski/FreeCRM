{strip}
<div class="editViewContainer" id="mailAccountEdit">
	<form class="form-horizontal" id="MailAccountForm">
		<input type="hidden" name="record" value="{$RECORD_ID}">
		<input type="hidden" name="kind" value="{$ACCOUNT_KIND}">
		{if $ACCOUNT_KIND eq 'personal'}
			<input type="hidden" name="owner_user_id" value="{$OWNER_USER_ID}">
		{/if}
		{if $ACCOUNT_KIND eq 'shared'}
			<div class="form-group">
				<label class="control-label col-md-3">{"LBL_CRM_GROUP"|t:$QUALIFIED_MODULE} *</label>
				<div class="col-md-8">
					<select class="select2 form-control" name="group_id" data-validation-engine="validate[required]">
						<option value="">—</option>
						{foreach from=$GROUP_OPTIONS key=GID item=GNAME}
							<option value="{$GID}" {if $MAIL_ACCOUNT.group_id|default:'' eq $GID}selected{/if}>{$GNAME}</option>
						{/foreach}
					</select>
					<p class="help-block text-muted small">{"LBL_CRM_GROUP_HELP"|t:$QUALIFIED_MODULE}</p>
				</div>
			</div>
		{else}
			<div class="form-group">
				<label class="control-label col-md-3">{"LBL_OWNER_USER"|t:$QUALIFIED_MODULE}</label>
				<div class="col-md-8">
					<p class="form-control-static">
						{$OWNER_USER_NAME|escape}
						{if $OWNER_PREFERENCE_MAILBOX_URL}
							<a class="marginLeft10px" href="{$OWNER_PREFERENCE_MAILBOX_URL}" target="_blank">{"LBL_OPEN_USER_MAILBOX"|t:$QUALIFIED_MODULE}</a>
						{/if}
					</p>
				</div>
			</div>
		{/if}
		<div class="col-md-offset-0 col-md-12">
			{include file='MailboxFields.tpl'|@vtemplate_path:'Mail'}
		</div>
		<div class="form-group">
			<div class="col-md-12">
				<button type="button" class="btn btn-default js-mailbox-test">{"LBL_TEST_CONNECTION"|t:$QUALIFIED_MODULE}</button>
				<span class="pull-right">
					<button type="button" class="btn btn-success js-mailbox-save"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>&nbsp;<strong>{"LBL_SAVE"|t:$QUALIFIED_MODULE}</strong></button>
					<button class="cancelLink btn btn-warning" type="button" onclick="javascript:window.history.back();"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span>&nbsp;{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
				</span>
			</div>
		</div>
	</form>
</div>
{/strip}
