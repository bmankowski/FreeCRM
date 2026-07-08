{strip}
{if !empty($MAIL_ACCOUNT.id)}
	<input type="hidden" name="id" value="{$MAIL_ACCOUNT.id}">
{/if}
{assign var=_mailboxMode value=$MAILBOX_FORM_MODE|default:$ACCOUNT_KIND|default:'group'}
{if $_mailboxMode eq 'personal'}
	<div class="form-group">
		<label>{"LBL_USERNAME"|t:"Mail"}</label>
		<p class="form-control-static">
			{if $USER_EMAIL|default:''}
				{$USER_EMAIL|escape}
			{else}
				<span class="text-muted">{"LBL_MAIL_USER_EMAIL_MISSING"|t:"Mail"}</span>
			{/if}
		</p>
	</div>
{else}
	<div class="form-group">
		<label>{"LBL_ACCOUNT_NAME"|t:"Mail"}</label>
		<input class="form-control" name="name" value="{$MAIL_ACCOUNT.name|default:''}">
	</div>
	<div class="form-group">
		<label>{"LBL_USERNAME"|t:"Mail"}</label>
		<input class="form-control" name="username" value="{$MAIL_ACCOUNT.username|default:''}">
	</div>
{/if}
<div class="form-group">
	<label>{"LBL_IMAP_HOST"|t:"Mail"}</label>
	<input class="form-control" name="imap_host" value="{$MAIL_ACCOUNT.imap_host|default:''}">
</div>
<div class="form-group">
	<label>{"LBL_IMAP_PORT"|t:"Mail"}</label>
	<input class="form-control" name="imap_port" value="{$MAIL_ACCOUNT.imap_port|default:'993'}">
</div>
<div class="form-group">
	<label>{"LBL_SMTP_HOST"|t:"Mail"}</label>
	<input class="form-control" name="smtp_host" value="{$MAIL_ACCOUNT.smtp_host|default:''}">
</div>
<div class="form-group">
	<label>{"LBL_SMTP_PORT"|t:"Mail"}</label>
	<input class="form-control" name="smtp_port" value="{$MAIL_ACCOUNT.smtp_port|default:'465'}">
</div>
<div class="form-group">
	<label>{"LBL_PASSWORD"|t:"Mail"}</label>
	<input class="form-control" type="password" name="password" value="" placeholder="**********" autocomplete="new-password">
</div>
<div class="form-group">
	<label>{"LBL_FROM_NAME"|t:"Mail"}</label>
	<input class="form-control" name="from_name" value="{$MAIL_ACCOUNT.from_name|default:''}">
</div>
{if $_mailboxMode neq 'personal'}
	{assign var=_replyToMode value=$MAIL_ACCOUNT.reply_to_mode|default:'same_as_from'}
	<div class="form-group">
		<label>{"LBL_REPLY_TO_MODE"|t:"Settings:MailAccount"}</label>
		<select class="form-control js-mail-reply-to-mode" name="reply_to_mode">
			<option value="same_as_from" {if $_replyToMode eq 'same_as_from'}selected{/if}>{"LBL_REPLY_TO_MODE_SAME_AS_FROM"|t:"Settings:MailAccount"}</option>
			<option value="user_personal" {if $_replyToMode eq 'user_personal'}selected{/if}>{"LBL_REPLY_TO_MODE_USER_PERSONAL"|t:"Settings:MailAccount"}</option>
			<option value="custom" {if $_replyToMode eq 'custom'}selected{/if}>{"LBL_REPLY_TO_MODE_CUSTOM"|t:"Settings:MailAccount"}</option>
		</select>
		<p class="help-block text-muted small">{"LBL_REPLY_TO_MODE_HELP"|t:"Settings:MailAccount"}</p>
	</div>
	<div class="form-group js-mail-reply-to-address-wrap"{if $_replyToMode neq 'custom'} style="display:none"{/if}>
		<label>{"LBL_REPLY_TO_ADDRESS"|t:"Settings:MailAccount"}</label>
		<input class="form-control" type="text" name="reply_to_address" value="{$MAIL_ACCOUNT.reply_to_address|default:''}" data-validation-engine="{if $_replyToMode eq 'custom'}validate[required,custom[email]]{/if}">
	</div>
{/if}
<div class="form-group">
	<label><input type="checkbox" name="append_sent" value="1" {if $MAIL_ACCOUNT.append_sent|default:1 eq 1}checked{/if}> {"LBL_APPEND_SENT"|t:"Mail"}</label>
</div>
<div class="form-group">
	<label>{"LBL_IMAP_FOLDER_SENT"|t:"Mail"}</label>
	<input class="form-control js-mail-imap-folder-sent" name="imap_folder_sent" value="{$MAIL_ACCOUNT.imap_folder_sent|default:''}">
	<p class="help-block text-muted small">{"LBL_IMAP_FOLDER_SENT_HELP"|t:"Mail"}</p>
	{include file='ImapFolderBrowser.tpl'|@vtemplate_path:'Mail'}
</div>
{/strip}
