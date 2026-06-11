{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1]} -->*}
{strip}
<!-- layouts/basic/modules/Base/partials/MailComposeAttachments.tpl -->
	<div class="form-group js-mail-attachments mail-compose-attachments"
		data-mail-attachment-limits='{\App\Utils\Json::encode($MAIL_COMPOSE_ATTACHMENT_LIMITS)|escape:'html'}'>
		<label class="control-label">{'LBL_ATTACHMENTS'|t:'Mail'}</label>
		<div class="mail-compose-attachment-pills">
			<ul class="list-unstyled js-mail-attachment-list mail-compose-attachment-list"></ul>
			<button type="button" class="btn btn-default btn-xs js-mail-attachment-add">
				<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
				<span>{'LBL_MAIL_ATTACHMENT_ADD_MORE'|t:'Mail'}</span>
			</button>
		</div>
		<div class="js-mail-template-attachments-wrap hide mail-compose-template-wrap">
			<div class="mail-compose-template-heading">
				<span class="glyphicon glyphicon-lock" aria-hidden="true"></span>
				<span>{'LBL_TEMPLATE_ATTACHMENTS'|t:'Mail'}</span>
			</div>
			<ul class="list-unstyled js-mail-template-attachment-list mail-compose-attachment-list mail-compose-attachment-list--template"></ul>
		</div>
		<input type="file" class="hide js-mail-attachment-input" multiple />
	</div>
<!--/layouts/basic/modules/Base/partials/MailComposeAttachments.tpl -->
{/strip}
