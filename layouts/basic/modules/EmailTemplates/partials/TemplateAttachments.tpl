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
			<div class="col-md-12 paddingLRZero fieldRow js-emailtemplate-attachment-field">
				<div class="col-md-9 fieldValue paddingLRZero">
					<div class="js-emailtemplate-attachment-select-wrap">
						<select id="EmailTemplates_attachment_select" title="{'LBL_STANDARD_ATTACHMENTS'|t:'EmailTemplates'}" multiple class="chzn-select form-control js-emailtemplate-attachment-select" disabled="disabled"></select>
					</div>

				</div>
				<div class="col-md-3 fieldLabel hidden-xs hidden-sm">
					<button type="button" class="btn btn-default js-emailtemplate-attachment-upload">
						<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
						<span>{'LBL_UPLOAD_ATTACHMENT'|t:'EmailTemplates'}</span>
					</button>
				</div>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/EmailTemplates/partials/TemplateAttachments.tpl -->
{/strip}
