{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1]} -->*}
{strip}
<!-- layouts/basic/modules/Base/partials/MailComposeAttachments.tpl -->
	<div class="form-group js-mail-attachments"
		data-mail-attachment-limits='{\App\Utils\Json::encode($MAIL_COMPOSE_ATTACHMENT_LIMITS)|escape:'html'}'>
		<label class="control-label">{'LBL_ATTACHMENTS'|t:'Mail'}</label>
		<div class="mail-compose-dropzone js-mail-attachment-dropzone">
			<input type="file" class="hide js-mail-attachment-input" multiple />
			<div class="mail-compose-dropzone__content">
				<span class="glyphicon glyphicon-paperclip" aria-hidden="true"></span>
				<span class="mail-compose-dropzone__text">{'LBL_DROP_ATTACHMENTS_HERE'|t:'Mail'}</span>
			</div>
		</div>
		<ul class="list-unstyled js-mail-attachment-list mail-compose-attachment-list"></ul>
		<div class="js-mail-template-attachments-wrap hide">
			<div class="help-block">{'LBL_TEMPLATE_ATTACHMENTS'|t:'Mail'}</div>
			<ul class="list-unstyled js-mail-template-attachment-list mail-compose-attachment-list mail-compose-template-attachments"></ul>
		</div>
	</div>
<!--/layouts/basic/modules/Base/partials/MailComposeAttachments.tpl -->
{/strip}
