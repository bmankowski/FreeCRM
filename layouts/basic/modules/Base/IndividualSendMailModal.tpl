{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1 that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/IndividualSendMailModal.tpl -->
	{assign var=IS_EMAIL value=false}
	<div class="modal-header">
		<button type="button" class="btn btn-warning btn-sm pull-right" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title">{"LBL_SEND_EMAIL"|t:$MODULE}</h4>
	</div>
	<div class="modal-body">
		{if !empty($SHOW_RECIPIENT_STATS)}
			<p class="text-muted" role="status" style="margin-bottom: 15px;">
				<span class="glyphicon glyphicon-user" aria-hidden="true"></span>&nbsp;
				{'LBL_NUMBER_OF_SELECTED_RECORDS'|t:$MODULE} {$RECORDS['all']}
				&nbsp;&middot;&nbsp;
				{'LBL_NUMBER_OF_FOUND_MAIL_ADDRESSES'|t:$MODULE} {$RECORDS['emails']}
			</p>
		{/if}
		<form class="validateForm"
			data-mail-compose-senders='{\App\Utils\Json::encode($COMPOSE_SENDERS)|escape:'html'}'>
			<div class="form-group">
				<label class="control-label" for="template">{'LBL_EMAIL_TEMPLATE'|t}</label>
				<select class="select2" id="template" data-validation-engine="validate[required]">
					{foreach item=ROW from=$TEMPLETE_LIST}
						<option value="{$ROW['id']}"
							data-sender-type="{$ROW['sender_type']|default:'system_smtp'|escape}"
							data-smtp-id="{$ROW['smtp_id']|default:''|escape}"
							data-default-sender-ref="{$ROW['default_sender_ref']|default:''|escape}">{$ROW['name']}</option>
					{/foreach}
				</select>
			</div>
			<div class="form-group js-mail-sender-picker hide">
				<label class="control-label" for="mailSender">{"LBL_SEND_FROM"|t:"Mail"}</label>
				<select class="select2" id="mailSender"></select>
			</div>
			<div class="form-group">
				<label class="control-label" for="field">{'LBL_EMAIL_ADRESS'|t}</label>
				<select class="select2" id="field" data-validation-engine="validate[required]">
					{foreach item=COUNT key=NAME from=$RECORDS}
						{if $NAME != 'all' && $NAME != 'emails' && $COUNT > 0}
							<option value="{$FIELDS[$NAME]->getName()}" {if !empty($INITIAL_FIELD) && $INITIAL_FIELD eq $NAME}selected{/if}>{if !empty($FIELD_EMAILS[$NAME])}{$FIELD_EMAILS[$NAME]|escape}{else}{$FIELDS[$NAME]->getFieldLabel()|t:$MODULE} ({$COUNT}){/if}</option>
							{assign var=IS_EMAIL value=true}
						{/if}
					{/foreach}
				</select>
			</div>
			<div class="js-mail-preview-section {if empty($INITIAL_PREVIEW['success'])}hide{/if}" data-mail-module="{$MODULE|escape:'html'}" data-mail-selected-ids='{\App\Utils\Json::encode($SELECTED_IDS)|escape:'html'}' data-mail-source-module="{$SOURCE_MODULE|escape:'html'}" data-mail-source-record="{$SOURCE_RECORD|escape:'html'}" data-mail-missing-source-context="{if !empty($INITIAL_PREVIEW['missingSourceContext'])}1{else}0{/if}">
				<div class="alert alert-warning js-source-context-warning {if empty($INITIAL_PREVIEW['missingSourceContext'])}hide{/if}" role="alert">
					<span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span>&nbsp;&nbsp;
					<span class="js-source-context-warning-text">{if !empty($INITIAL_PREVIEW['warning'])}{\App\Modules\Base\Helpers\Util::toSafeHTML($INITIAL_PREVIEW['warning'])}{/if}</span>
				</div>
				<div class="form-group">
					<label class="control-label" for="mailSubject"><span class="redColor">*</span> {'LBL_SUBJECT'|t}</label>
					<input type="text" class="form-control js-mail-subject" id="mailSubject" data-validation-engine="validate[required]" value="{if !empty($INITIAL_PREVIEW['subject'])}{\App\Modules\Base\Helpers\Util::toSafeHTML($INITIAL_PREVIEW['subject'])}{/if}" />
				</div>
				<div class="form-group">
					<div class="c-mail-body">
						<div class="c-mail-body__editor">
							<textarea class="hide js-mail-content-input" id="mailContent" data-validation-engine="validate[required]"></textarea>
							<div class="form-control js-mail-content c-mail-body__content" contenteditable="true">{if !empty($INITIAL_PREVIEW['content'])}{$INITIAL_PREVIEW['content']}{/if}</div>
						</div>
						<div class="c-mail-body__ai" role="toolbar" aria-label="{'LBL_AI_IMPROVE_MAIL'|t}">
							<button type="button" class="btn btn-default btn-sm c-mail-body__ai-btn c-mail-body__ai-btn--primary js-ai-improve-mail" title="{'LBL_AI_IMPROVE_MAIL'|t}" aria-label="{'LBL_AI_IMPROVE_MAIL'|t}">
								<span class="fa fa-magic" aria-hidden="true"></span>
							</button>
							<span class="c-mail-body__ai-sep" aria-hidden="true"></span>
							<button type="button" class="btn btn-default btn-sm c-mail-body__ai-btn js-ai-improve-undo" disabled="disabled" title="{'LBL_AI_IMPROVE_UNDO'|t}">
								<span class="glyphicon glyphicon-share-alt c-mail-body__ai-undo-icon" aria-hidden="true"></span>
								<span class="c-mail-body__ai-label">{'LBL_AI_IMPROVE_UNDO'|t}</span>
							</button>
							<button type="button" class="btn btn-default btn-sm c-mail-body__ai-btn js-ai-improve-redo" disabled="disabled" title="{'LBL_AI_IMPROVE_REDO'|t}">
								<span class="glyphicon glyphicon-share-alt" aria-hidden="true"></span>
								<span class="c-mail-body__ai-label">{'LBL_AI_IMPROVE_REDO'|t}</span>
							</button>
						</div>
					</div>
				</div>
				{include file='partials/MailComposeAttachments.tpl'|@vtemplate_path:'Base'}
			</div>
		</form>
		{if !$CAN_SEND_MAIL}
			<div class="alert alert-danger" role="alert">
				<span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span>&nbsp;&nbsp;
				{"LBL_NO_MAIL_ACCOUNT"|t:"Mail"}
			</div>
		{elseif empty($TEMPLETE_LIST)}
			<div class="alert alert-warning" role="alert">
				<span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span>&nbsp;&nbsp;
				{'LBL_NO_DATA'|t}
			</div>
		{/if}
	</div>
	<div class="modal-footer">
		{if $CAN_SEND_MAIL && $TEMPLETE_LIST && $IS_EMAIL}
			<button class="btn btn-success" type="submit" name="saveButton">
				<strong>{'LBL_SEND'|t}</strong>
			</button>
		{/if}
		<button class="btn btn-warning" type="reset" data-dismiss="modal">
			<strong>{'LBL_CANCEL'|t}</strong>
		</button>
	</div>
<!--/layouts/basic/modules/Base/IndividualSendMailModal.tpl -->
{/strip}
