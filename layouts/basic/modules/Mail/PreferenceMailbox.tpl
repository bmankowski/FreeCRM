{strip}
<div class="panel panel-default mailPreferencePanel" id="mailPreferencePanel">
	<div class="panel-heading"><strong>{"LBL_MAILBOX"|t:"Mail"}</strong></div>
	<div class="panel-body">
		<p class="text-muted">{"LBL_MAILBOX_HELP"|t:"Mail"}</p>
		<form id="PersonalMailboxForm">
			<input type="hidden" name="owner_user_id" value="{$RECORD_ID}">
			{if !empty($MAIL_ACCOUNT.id)}
				<input type="hidden" name="id" value="{$MAIL_ACCOUNT.id}">
			{/if}
			<div class="form-group">
				<label>{"LBL_ACCOUNT_NAME"|t:"Mail"}</label>
				<input class="form-control" name="name" value="{$MAIL_ACCOUNT.name|default:''}">
			</div>
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
				<label>{"LBL_USERNAME"|t:"Mail"}</label>
				<input class="form-control" name="username" value="{$MAIL_ACCOUNT.username|default:''}">
			</div>
			<div class="form-group">
				<label>{"LBL_PASSWORD"|t:"Mail"}</label>
				<input class="form-control" type="password" name="password" value="" placeholder="**********" autocomplete="new-password">
			</div>
			<div class="form-group">
				<label>{"LBL_FROM_NAME"|t:"Mail"}</label>
				<input class="form-control" name="from_name" value="{$MAIL_ACCOUNT.from_name|default:''}">
			</div>
			<div class="form-group">
				<label><input type="checkbox" name="append_sent" value="1" {if $MAIL_ACCOUNT.append_sent|default:1 eq 1}checked{/if}> {"LBL_APPEND_SENT"|t:"Mail"}</label>
			</div>
			<div class="form-group">
				<label>{"LBL_IMAP_FOLDER_SENT"|t:"Mail"}</label>
				<input class="form-control js-mail-imap-folder-sent" name="imap_folder_sent" value="{$MAIL_ACCOUNT.imap_folder_sent|default:''}">
				<p class="help-block text-muted small">{"LBL_IMAP_FOLDER_SENT_HELP"|t:"Mail"}</p>
			</div>
			{include file='ImapFolderBrowser.tpl'|@vtemplate_path:'Mail'}
		</form>
	</div>
</div>
{/strip}
