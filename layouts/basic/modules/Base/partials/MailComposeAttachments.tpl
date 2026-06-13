{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1]} -->*}
{strip}
<!-- layouts/basic/modules/Base/partials/MailComposeAttachments.tpl -->
	<div class="form-group js-mail-attachments"
		data-mail-attachment-limits='{\App\Utils\Json::encode($MAIL_COMPOSE_ATTACHMENT_LIMITS)|escape:'html'}'>
		<label class="control-label">{'LBL_ATTACHMENTS'|t:'Mail'}</label>
		<div class="btn-toolbar js-mail-attachment-toolbar">
			<div class="btn-group js-mail-attachment-add-wrap">
				<button type="button" class="btn btn-default btn-xs js-mail-attachment-add">
					<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
					<span>{'LBL_MAIL_ATTACHMENT_ADD_MORE'|t:'Mail'}</span>
				</button>
			</div>
		</div>
		<div class="js-mail-template-attachments-wrap hide">
			<p class="small text-muted">
				<span class="glyphicon glyphicon-lock" aria-hidden="true"></span>
				<span>{'LBL_TEMPLATE_ATTACHMENTS'|t:'Mail'}</span>
			</p>
			<div class="btn-toolbar js-mail-template-attachment-list"></div>
			<p class="small text-warning hide js-mail-template-attachments-warning"></p>
		</div>
		<input type="file" class="hide js-mail-attachment-input" multiple />
	</div>
<!--/layouts/basic/modules/Base/partials/MailComposeAttachments.tpl -->
{/strip}
