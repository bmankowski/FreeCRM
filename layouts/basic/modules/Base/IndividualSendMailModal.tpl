{*<!-- {[The file is published on the basis of FreeCRM Public License 1.1 that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/IndividualSendMailModal.tpl -->
	{assign var=DEFAULT_SMTP value=App\Email\Mail::getDefaultSmtp()}
	{assign var=TEMPLETE_LIST value=App\Email\Mail::getTempleteList($TEMPLATE_MODULE)}
	{assign var=IS_EMAIL value=false}
	<div class="modal-header">
		<button type="button" class="btn btn-warning btn-sm pull-right" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title">{"LBL_SEND_EMAIL"|t:$MODULE}</h4>
	</div>
	<div class="modal-body">
		<form class="validateForm">
			<div class="form-group">
				<label class="control-label" for="field">{'LBL_EMAIL_ADRESS'|t}</label>
				<select class="select2" id="field" data-validation-engine="validate[required]">
					{foreach item=COUNT key=NAME from=$RECORDS}
						{if $NAME != 'all' && $NAME != 'emails' && $COUNT > 0}
							<option value="{$FIELDS[$NAME]->getName()}">{$FIELDS[$NAME]->getFieldLabel()|t:$MODULE} ({$COUNT})</option>
							{assign var=IS_EMAIL value=true}
						{/if}
					{/foreach}
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="template">{'LBL_EMAIL_TEMPLATE'|t}</label>
				<select class="select2" id="template" data-validation-engine="validate[required]">
					{foreach item=ROW from=$TEMPLETE_LIST}
						<option value="{$ROW['id']}">{$ROW['name']}</option>
					{/foreach}
				</select>
			</div>
			<div class="js-mail-preview-section {if empty($INITIAL_PREVIEW['success'])}hide{/if}">
				<div class="alert alert-warning js-source-context-warning {if empty($INITIAL_PREVIEW['missingSourceContext'])}hide{/if}" role="alert">
					<span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span>&nbsp;&nbsp;
					<span class="js-source-context-warning-text">{if !empty($INITIAL_PREVIEW['warning'])}{\App\Modules\Base\Helpers\Util::toSafeHTML($INITIAL_PREVIEW['warning'])}{/if}</span>
				</div>
				<div class="form-group">
					<label class="control-label" for="mailSubject">{'LBL_SUBJECT'|t}</label>
					<input type="text" class="form-control js-mail-subject" id="mailSubject" data-validation-engine="validate[required]" value="{if !empty($INITIAL_PREVIEW['subject'])}{\App\Modules\Base\Helpers\Util::toSafeHTML($INITIAL_PREVIEW['subject'])}{/if}" />
				</div>
				<div class="form-group">
					<label class="control-label">{'LBL_TEMPLATE_REAL_DATA_PREVIEW'|t}</label>
					<div class="help-block">{'LBL_TEMPLATE_REAL_DATA_PREVIEW_DESC'|t}</div>
					<textarea class="hide js-mail-content-input" id="mailContent" data-validation-engine="validate[required]"></textarea>
					<div class="form-control js-mail-content" contenteditable="true" style="background:#fff;border:1px solid #ccc;height:360px;overflow:auto;padding:18px;">{if !empty($INITIAL_PREVIEW['content'])}{$INITIAL_PREVIEW['content']}{/if}</div>
				</div>
			</div>
		</form>
		{if !$DEFAULT_SMTP}
			<div class="alert alert-danger" role="alert">
				<span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span>&nbsp;&nbsp;
				{'ERR_NO_DEFAULT_SMTP'|t}
			</div>
		{/if}
	</div>
	<div class="modal-footer">
		{if $DEFAULT_SMTP && $TEMPLETE_LIST && $IS_EMAIL}
			<button class="btn btn-success" type="submit" name="saveButton">
				<strong>{'LBL_SEND'|t}</strong>
			</button>
		{/if}
		<button class="btn btn-warning" type="reset" data-dismiss="modal">
			<strong>{'LBL_CANCEL'|t}</strong>
		</button>
	</div>
	<script>
		(function () {
			var modalContainer = jQuery('.js-mail-preview-section').last().closest('.modal-content');
			var previewSection = modalContainer.find('.js-mail-preview-section');
			var sourceWarning = modalContainer.find('.js-source-context-warning');
			var sourceWarningText = modalContainer.find('.js-source-context-warning-text');
			var subjectInput = modalContainer.find('.js-mail-subject');
			var contentEditor = modalContainer.find('.js-mail-content');
			var contentInput = modalContainer.find('.js-mail-content-input');
			var saveButton = modalContainer.find('[name="saveButton"]');
			var updateSourceWarning = function (result) {
				var hasMissingContext = !!(result && result.missingSourceContext);
				if (!sourceWarning.length || !saveButton.length) {
					return;
				}
				if (hasMissingContext) {
					sourceWarningText.text(result.warning || 'Ten szablon wymaga kontekstu projektu (sourceRecord).');
					sourceWarning.removeClass('hide');
					saveButton.prop('disabled', true).addClass('disabled');
				} else {
					sourceWarning.addClass('hide');
					saveButton.prop('disabled', false).removeClass('disabled');
				}
			};
			var syncMailContent = function () {
				contentInput.val(contentEditor.html());
			};
			var baseData = {
				module: '{$MODULE}',
				selected_ids: '{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($SELECTED_IDS))}',
				sourceModule: '{$SOURCE_MODULE}',
				sourceRecord: '{$SOURCE_RECORD}'
			};
			var loadPreview = function () {
				var previewData = jQuery.extend({}, baseData, {
					field: modalContainer.find('#field').val(),
					template: modalContainer.find('#template').val(),
					action: 'Mail',
					mode: 'previewMail'
				});
				AppConnector.request(previewData).then(function (previewResponse) {
					var result = previewResponse && previewResponse.result ? previewResponse.result : {};
					if (!result.success) {
						previewSection.addClass('hide');
						updateSourceWarning({});
						return;
					}
					subjectInput.val(result.subject || '');
					contentEditor.html(app.prepareMailEditorContent(result.content || ''));
					syncMailContent();
					updateSourceWarning(result);
					previewSection.removeClass('hide');
				}, function (data, err) {
					previewSection.addClass('hide');
					updateSourceWarning({});
					app.errorLog(data, err);
				});
			};
			modalContainer.find('#field, #template').on('change', loadPreview);
			contentEditor.on('input blur', syncMailContent);
			contentEditor.on('mousedown', function (e) {
				if (e.target === contentEditor[0]) {
					e.preventDefault();
					app.focusMailEditorStart(contentEditor);
				}
			});
			modalContainer.find('[name="saveButton"]').on('click', function () {
				if (saveButton.prop('disabled')) {
					return false;
				}
				syncMailContent();
				if (modalContainer.find('form').validationEngine('validate')) {
					var sendData = jQuery.extend({}, baseData, {
						field: modalContainer.find('#field').val(),
						template: modalContainer.find('#template').val(),
						subject: subjectInput.val(),
						content: contentEditor.html(),
						action: 'Mail',
						mode: 'sendMails'
					});
					AppConnector.request(sendData).then(function (response) {
						if (response.result == true) {
							app.hideModalWindow();
						}
					}, function (data, err) {
						app.hideModalWindow();
						app.errorLog(data, err);
					});
				}
			});
			if (contentEditor.html().trim()) {
				contentEditor.html(app.prepareMailEditorContent(contentEditor.html()));
				syncMailContent();
				updateSourceWarning({ldelim}missingSourceContext: {if !empty($INITIAL_PREVIEW['missingSourceContext'])}true{else}false{/if}{rdelim});
			} else {
				loadPreview();
			}
		}());
	</script>
<!--/layouts/basic/modules/Base/IndividualSendMailModal.tpl -->
{/strip}
