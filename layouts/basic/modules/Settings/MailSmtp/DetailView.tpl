{strip} {*
<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div class="widget_header row">
		<div class="col-md-8">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{'LBL_MAILSMTP_TO_SEND_DESCRIPTION'|t:$QUALIFIED_MODULE}
		</div>
		<div class="col-md-4 marginbottomZero">
			<div class="pull-right btn-toolbar"><span class="actionImages">
					<a class="btn btn-info" href="{$RECORD_MODEL->getEditViewUrl()}">
						<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>&nbsp;
						<strong>{'LBL_EDIT_RECORD'|t:$QUALIFIED_MODULE}</strong>
					</a>
					<a class="btn btn-danger marginLeft5" href="{$RECORD_MODEL->getDeleteActionUrl()}">
						<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>&nbsp;
						<strong>{'LBL_DELETE_RECORD'|t:$QUALIFIED_MODULE}</strong>
					</a>
			</div>
		</div>
	</div>
	<div class="detailViewInfo">
		{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
		<table class="table table-bordered">
			<thead>
				<tr class="blockHeader">
					<th colspan="2" class="{$WIDTHTYPE} col-md-12"><strong>{'LBL_SMTP_DETAIL'|t:$QUALIFIED_MODULE}</strong></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_NAME'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('name')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_MAILER_TYPE'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('mailer_type')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_DEFAULT'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('default')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_HOST'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('host')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_PORT'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('port')}
					</td>
				</tr>
					<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_AUTHENTICATION'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('authentication')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_USERNAME'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('username')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_PASSWORD'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('password')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_INDIVIDUAL_DELIVERY'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('individual_delivery')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_SECURE'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('secure')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_INDIVIDUAL_DELIVERY'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('individual_delivery')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_FROM_NAME'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('from_name')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_FROM_EMAIL'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('from_email')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_REPLY_TO'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('reply_to')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_OPTIONS'|t:$QUALIFIED_MODULE}</label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('options')}
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	{strip}