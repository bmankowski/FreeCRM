{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1]} -->*}
{strip}
<!-- layouts/basic/modules/EmailTemplates/partials/TemplateAttachments.tpl -->
	<div class="panel panel-default row marginLeftZero marginRightZero blockContainer js-emailtemplate-attachments"
		data-mail-attachment-limits='{\App\Utils\Json::encode($MAIL_ATTACHMENT_LIMITS)|escape:'html'}'>
		<div class="row blockHeader panel-heading marginLeftZero marginRightZero">
			<div class="iconCollapse">
				<h4>{'LBL_STANDARD_ATTACHMENTS'|t:'EmailTemplates'}</h4>
			</div>
		</div>
		<div class="col-md-12 paddingLRZero panel-body blockContent">
			<p class="small text-muted js-emailtemplate-attachments-save-first hide">
				{'LBL_SAVE_TEMPLATE_FIRST'|t:'EmailTemplates'}
			</p>
			<div class="btn-toolbar js-emailtemplate-attachment-toolbar">
				<div class="btn-group js-emailtemplate-attachment-list"></div>
				<div class="btn-group js-emailtemplate-attachment-actions">
					<button type="button" class="btn btn-default btn-xs js-emailtemplate-attachment-upload" disabled="disabled">
						<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
						<span>{'LBL_UPLOAD_ATTACHMENT'|t:'EmailTemplates'}</span>
					</button>
					<button type="button" class="btn btn-default btn-xs js-emailtemplate-attachment-select" disabled="disabled">
						<span class="glyphicon glyphicon-paperclip" aria-hidden="true"></span>
						<span>{'LBL_SELECT_ATTACHMENT'|t:'EmailTemplates'}</span>
					</button>
				</div>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/EmailTemplates/partials/TemplateAttachments.tpl -->
{/strip}
