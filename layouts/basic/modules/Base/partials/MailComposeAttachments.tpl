{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1]} -->*}
{strip}
<!-- layouts/basic/modules/Base/partials/MailComposeAttachments.tpl -->
	<div class="form-group js-mail-attachments mail-compose-attachments"
		data-mail-attachment-limits='{\App\Utils\Json::encode($MAIL_COMPOSE_ATTACHMENT_LIMITS)|escape:'html'}'>
		<div class="mail-compose-attachments-panel">
			<div class="mail-compose-attachments-panel__header">
				<span class="mail-compose-attachments-panel__title">{'LBL_ATTACHMENTS'|t:'Mail'}</span>
				<span class="js-mail-attachment-summary mail-compose-attachments-panel__summary hide" aria-live="polite"></span>
			</div>
			<div class="mail-compose-attachments-panel__body">
				<ul class="list-unstyled js-mail-attachment-list mail-compose-attachment-list"></ul>
				<div class="mail-compose-dropzone js-mail-attachment-dropzone">
					<div class="mail-compose-dropzone__empty">
						<div class="mail-compose-dropzone__icon" aria-hidden="true">
							<span class="glyphicon glyphicon-paperclip"></span>
						</div>
						<div class="mail-compose-dropzone__text">
							<span class="mail-compose-dropzone__main">{'LBL_DROP_ATTACHMENTS_HERE'|t:'Mail'}</span>
						</div>
					</div>
					<div class="mail-compose-dropzone__add-more">
						<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
						<span class="mail-compose-dropzone__add-more-text">{'LBL_MAIL_ATTACHMENT_ADD_MORE'|t:'Mail'}</span>
					</div>
				</div>
				<div class="js-mail-template-attachments-wrap hide mail-compose-template-wrap">
					<div class="mail-compose-template-heading">
						<span class="glyphicon glyphicon-lock" aria-hidden="true"></span>
						<span>{'LBL_TEMPLATE_ATTACHMENTS'|t:'Mail'}</span>
					</div>
					<ul class="list-unstyled js-mail-template-attachment-list mail-compose-attachment-list mail-compose-attachment-list--template"></ul>
				</div>
			</div>
			<div class="mail-compose-attachments-panel__footer">
				<span class="mail-compose-attachments-panel__limits">{sprintf('LBL_MAIL_ATTACHMENT_LIMITS_HINT'|t:'Mail', $MAIL_COMPOSE_ATTACHMENT_LIMITS['maxFiles'], $MAIL_COMPOSE_ATTACHMENT_LIMITS['maxFileMb'], $MAIL_COMPOSE_ATTACHMENT_LIMITS['maxTotalMb'])}</span>
				<span class="mail-compose-attachments-panel__modal-hint">{'LBL_MAIL_ATTACHMENT_MODAL_DROP'|t:'Mail'}</span>
			</div>
		</div>
		<input type="file" class="hide js-mail-attachment-input" multiple />
	</div>
<!--/layouts/basic/modules/Base/partials/MailComposeAttachments.tpl -->
{/strip}
