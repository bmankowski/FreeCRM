{strip}
<div class="editViewContainer" id="mailAccountEdit">
	<form class="form-horizontal" id="MailAccountForm">
		<input type="hidden" name="record" value="{$RECORD_ID}">
		<input type="hidden" name="kind" value="shared">
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_NAME"|t:$QUALIFIED_MODULE} *</label>
			<div class="col-md-8"><input class="form-control" name="name" value="{$RECORD_MODEL->get('name')}" required></div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_IMAP_HOST"|t:$QUALIFIED_MODULE} *</label>
			<div class="col-md-8"><input class="form-control" name="imap_host" value="{$RECORD_MODEL->get('imap_host')}"></div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_IMAP_PORT"|t:$QUALIFIED_MODULE}</label>
			<div class="col-md-8"><input class="form-control" name="imap_port" value="{$RECORD_MODEL->get('imap_port')|default:993}"></div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_SMTP_HOST"|t:$QUALIFIED_MODULE} *</label>
			<div class="col-md-8"><input class="form-control" name="smtp_host" value="{$RECORD_MODEL->get('smtp_host')}"></div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_SMTP_PORT"|t:$QUALIFIED_MODULE}</label>
			<div class="col-md-8"><input class="form-control" name="smtp_port" value="{$RECORD_MODEL->get('smtp_port')|default:465}"></div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_USERNAME"|t:$QUALIFIED_MODULE} *</label>
			<div class="col-md-8"><input class="form-control" name="username" value="{$RECORD_MODEL->get('username')}"></div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_PASSWORD"|t:$QUALIFIED_MODULE}</label>
			<div class="col-md-8"><input class="form-control" type="password" name="password" value="{$RECORD_MODEL->get('password')|default:'**********'}" autocomplete="new-password"></div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_FROM_NAME"|t:$QUALIFIED_MODULE}</label>
			<div class="col-md-8"><input class="form-control" name="from_name" value="{$RECORD_MODEL->get('from_name')}"></div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_ASSIGNED_USERS"|t:$QUALIFIED_MODULE}</label>
			<div class="col-md-8">
				<select class="select2 form-control" name="assigned_users[]" multiple>
					{foreach from=$USER_OPTIONS key=UID item=LABEL}
						<option value="{$UID}" {if in_array($UID, $RECORD_MODEL->get('assigned_users'))}selected{/if}>{$LABEL}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-md-3">{"LBL_IMAP_FOLDER_SENT"|t:$QUALIFIED_MODULE}</label>
			<div class="col-md-8">
				<input class="form-control js-mail-imap-folder-sent" name="imap_folder_sent" value="{$RECORD_MODEL->get('imap_folder_sent')}">
				<p class="help-block text-muted small">{"LBL_IMAP_FOLDER_SENT_HELP"|t:"Mail"}</p>
				{include file='ImapFolderBrowser.tpl'|@vtemplate_path:'Mail'}
			</div>
		</div>
		<div class="form-group">
			<div class="col-md-offset-3 col-md-8">
				<button type="button" class="btn btn-default js-mail-test-connection">{"LBL_TEST_CONNECTION"|t:$QUALIFIED_MODULE}</button>
				<button type="button" class="btn btn-success js-mail-save-account">{"LBL_SAVE"|t:$QUALIFIED_MODULE}</button>
			</div>
		</div>
	</form>
</div>
{/strip}
