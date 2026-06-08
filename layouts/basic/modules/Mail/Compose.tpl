{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}
{block name="content"}
<div class="mainContainer"><div class="contentsDiv col-md-8 col-md-offset-2">
	<h3>{"LBL_COMPOSE"|t:"Mail"}</h3>
	{if !$CAN_SEND}
		<div class="alert alert-warning">{"LBL_CONFIGURE_MAILBOX"|t:"Mail"}</div>
	{/if}
	<form id="mail-compose-form"
		data-mail-compose-senders='{\App\Utils\Json::encode($COMPOSE_SENDERS)|escape:'html'}'>
		<input type="hidden" name="sourceModule" value="{$SOURCE_MODULE|escape}" />
		<input type="hidden" name="sourceRecord" value="{$SOURCE_RECORD|escape}" />
		<div class="form-group">
			<label>{"LBL_TO"|t:"Mail"}</label>
			<input type="text" class="form-control" name="to" value="{$TO|escape}" data-validation-engine="validate[required]" />
		</div>
		<div class="form-group">
			<label>{"LBL_EMAIL_TEMPLATE"|t:"Vtiger"}</label>
			<select class="form-control" id="mail-template" name="templateId">
				<option value="">—</option>
				{foreach from=$TEMPLATES item=TPL}
					<option value="{$TPL.id}"
						data-sender-type="{$TPL.sender_type|default:'system_smtp'|escape}"
						data-smtp-id="{$TPL.smtp_id|default:''|escape}"
						data-default-sender-ref="{$TPL.default_sender_ref|default:''|escape}">{$TPL.name|escape}</option>
				{/foreach}
			</select>
		</div>
		<div class="form-group js-mail-sender-picker hide">
			<label>{"LBL_SEND_FROM"|t:"Mail"}</label>
			<select class="form-control" id="mail-sender"></select>
		</div>
		<div class="form-group">
			<label>{"LBL_SUBJECT"|t:"Vtiger"}</label>
			<input type="text" class="form-control" name="subject" data-validation-engine="validate[required]" />
		</div>
		<div class="form-group">
			<label>{"LBL_BODY"|t:"Mail"}</label>
			<textarea class="form-control" name="content" rows="12" data-validation-engine="validate[required]"></textarea>
		</div>
		<button type="button" class="btn btn-success js-mail-send" {if !$CAN_SEND}disabled{/if}>{"LBL_SEND"|t:"Vtiger"}</button>
	</form>
</div></div>
{/block}
{/strip}
