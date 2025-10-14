{strip}
<!-- layouts/basic/modules/Settings/MailSmtp/Edit.tpl --> {*
<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div class="row widget_header">
		<div class="col-xs-12">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{if $RECORD_ID}
				{'LBL_MAILSMTP_EDIT'|t:$QUALIFIED_MODULE}
			{/if}
		</div>
	</div>
	<div class="editViewContainer">
		<form name="EditMailSmtp"  id="EditView" class="form-horizontal validateForm">
			<div class="alert alert-block alert-danger fade in " hidden="">
				<button type="button" class="close" data-dismiss="alert">×</button>
				<h4 class="alert-heading">{'LBL_ERROR'|t:$QUALIFIED_MODULE}</h4>
				<p></p>
			</div>
			<input type="hidden" name="module" value="MailSmtp">
			<input type="hidden" name="parent" value="Settings" />
			<input type="hidden" name="action" value="Save">
			<input type="hidden" name="mode" value="save">
			<input type="hidden" name="record" value="{$RECORD_ID}">
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_NAME'|t:$QUALIFIED_MODULE} <span class="redColor"> *
				</label>
				<div class="controls col-md-8">
					</span><input class="form-control" type="text" name="name" value="{$RECORD_MODEL->get('name')}" data-validation-engine="validate[required]"> 
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_MAILER_TYPE'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<select class="select2 form-control sourceModule col-md-8" name="mailer_type" id="mailerType">
						<option {if $RECORD_MODEL->get('mailer_type') eq 'smtp'} selected {/if} value="smtp">{'LBL_SMTP'|t:$QUALIFIED_MODULE}</option>
						<option {if $RECORD_MODEL->get('mailer_type') eq 'sendmail'} selected {/if} value="sendmail">{'LBL_SENDMAIL'|t:$QUALIFIED_MODULE}</option>
						<option {if $RECORD_MODEL->get('mailer_type') eq 'mail'} selected {/if} value="mail">{'LBL_MAIL'|t:$QUALIFIED_MODULE}</option>
						<option {if $RECORD_MODEL->get('mailer_type') eq 'qmail'} selected {/if} value="qmail">{'LBL_QMAIL'|t:$QUALIFIED_MODULE}</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_DEFAULT'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input type="checkbox" name="default" value="1" {if $RECORD_MODEL->get('default') eq 1} checked {/if}>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_HOST'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input class="form-control" type="text" name="host" value="{$RECORD_MODEL->get('host')}" >
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_PORT'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input class="form-control" type="text" name="port" value="{$RECORD_MODEL->get('port')}"  data-validation-engine="validate[custom[integer]]">
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_AUTHENTICATION'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input type="checkbox" name="authentication" value="1"  {if $RECORD_MODEL->get('authentication') eq 1} checked {/if}>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_USERNAME'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input class="form-control" type="text" value="{$RECORD_MODEL->get('username')}" name="username" >
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_PASSWORD'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input class="form-control" type="password" value="{$RECORD_MODEL->get('password')}" name="password" >
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_INDIVIDUAL_DELIVERY'|t:$QUALIFIED_MODULE}&nbsp;
					<span class="popoverTooltip"  data-placement="top"
						  data-content="{'LBL_INDIVIDUAL_DELIVERY_INFO'|t:$QUALIFIED_MODULE}">
						<span class="glyphicon glyphicon-info-sign"></span>
					</span>
				</label>
				<div class="controls col-md-8">
					<input type="checkbox" name="individual_delivery" value="1" {if $RECORD_MODEL->get('individual_delivery') eq 1} checked {/if}>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_SECURE'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<select class="select2 form-control sourceModule col-md-8" name="secure" id="secure">
						<option  value="">{'LBL_SELECT_OPTION'|t:$QUALIFIED_MODULE}</option>
						<option {if $RECORD_MODEL->get('secure') eq 'tls'} selected {/if} value="tls">{'LBL_TLS'|t:$QUALIFIED_MODULE}</option>
						<option {if $RECORD_MODEL->get('secure') eq 'ssl'} selected {/if} value="ssl">{'LBL_SSL'|t:$QUALIFIED_MODULE}</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_FROM_NAME'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input class="form-control" type="text" name="from_name"  value="{$RECORD_MODEL->get('from_name')}">
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_FROM_EMAIL'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input class="form-control" type="text" value="{$RECORD_MODEL->get('from_email')}" name="from_email"  data-validation-engine="validate[custom[email]]">
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_REPLY_TO'|t:$QUALIFIED_MODULE}
				</label>
				<div class="controls col-md-8">
					<input class="form-control" type="text" name="reply_to"  value="{$RECORD_MODEL->get('reply_to')}" data-validation-engine="validate[custom[email]]">
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-md-3">
					{'LBL_OPTIONS'|t:$QUALIFIED_MODULE}&nbsp;
					<span class="popoverTooltip delay0"  data-placement="top"
						  data-content="{'LBL_OPTIONS_INFO'|t:$QUALIFIED_MODULE}">
						<span class="glyphicon glyphicon-info-sign"></span>
					</span>
				</label>
				<div class="controls col-md-8">
					<textarea class="form-control" name="options">{$RECORD_MODEL->get('options')}</textarea>
				</div>
			</div>
			<div class="row">
				<div class="col-md-5 pull-right">
					<span class="pull-right">
						<button class="btn btn-success" type="submit"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>&nbsp;<strong>{'LBL_SAVE'|t:$QUALIFIED_MODULE}</strong></button>
						<button class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span>&nbsp;{'LBL_CANCEL'|t:$QUALIFIED_MODULE}</button>
					</span>
				</div>
			</div>
		</form>
	</div>
<!--/layouts/basic/modules/Settings/MailSmtp/Edit.tpl -->
{/strip}