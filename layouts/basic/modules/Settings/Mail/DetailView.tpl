{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
		<div class="mainContainer">
			<div class="contentsDiv">
				
<!-- layouts/basic/modules/Settings/Mail/DetailView.tpl -->
	{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div class="widget_header row">
		<div class="col-md-8">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{'LBL_EMAILS_TO_SEND_DESCRIPTION'|t:$QUALIFIED_MODULE}
		</div>
		<div class="col-md-4 marginbottomZero">
			{if $RECORD_MODEL}
				<div class="pull-right btn-toolbar"><span class="actionImages">
						<button class="btn btn-info sendManually">
							<span class="glyphicon glyphicon-send"></span>
							<strong class="marginLeft5">{'LBL_MANUAL_SENDING'|t:$QUALIFIED_MODULE}</strong>
						</button>
						{if $RECORD_MODEL->get('status') eq 0}
							<button class="btn btn-success acceptanceRecord marginLeft5">
								<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>&nbsp;
								<strong>{'LBL_ACCEPTANCE_RECORD'|t:$QUALIFIED_MODULE}</strong>
							</button>
						{/if}
						<a class="btn btn-danger marginLeft5 deleteButton" href="{$RECORD_MODEL->getDeleteActionUrl()}">
							<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>&nbsp;
							<strong>{'LBL_DELETE_RECORD'|t:$QUALIFIED_MODULE}</strong>
						</a>
				</div>
			{/if}
		</div>
	</div>
	<div class="detailViewInfo">
		{if $RECORD_MODEL}
		<input type="hidden" value="{$RECORD_MODEL->getId()}" id="recordId">
		{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
		<table class="table table-bordered">
			<thead>
				<tr class="blockHeader">
					<th colspan="2" class="{$WIDTHTYPE} col-md-8"><strong>{'LBL_EMAIL_DETAIL'|t:$QUALIFIED_MODULE}</strong></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_SMTP_NAME'|t:$QUALIFIED_MODULE} </label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('smtp_id')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_DATE'|t:$QUALIFIED_MODULE}  </label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('date')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_CREATED_BY'|t:$QUALIFIED_MODULE}  </label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('owner')}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_PRIORITY'|t:$QUALIFIED_MODULE}  </label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('priority')|t}
					</td>
				</tr>
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_STATUS'|t:$QUALIFIED_MODULE}  </label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('status')}
					</td>
				</tr>
				{if !empty($RECORD_MODEL->getDisplayValue('from'))}
					<tr>
						<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_FROM'|t:$QUALIFIED_MODULE}  </label></td>
						<td class="{$WIDTHTYPE} col-md-8">
							{$RECORD_MODEL->getDisplayValue('from')}
						</td>
					</tr>
				{/if}
				{if !empty($RECORD_MODEL->getDisplayValue('to'))}
					<tr>
						<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_TO'|t:$QUALIFIED_MODULE}  </label></td>
						<td class="{$WIDTHTYPE} col-md-8">
							{$RECORD_MODEL->getDisplayValue('to')}
						</td>
					</tr>
				{/if}
				{if !empty($RECORD_MODEL->getDisplayValue('cc'))}
					<tr>
						<td class="{$WIDTHTYPE} col-md-3 "><label class="pull-right">{'LBL_CC'|t:$QUALIFIED_MODULE}  </label></td>
						<td class="{$WIDTHTYPE} col-md-8">
							{$RECORD_MODEL->getDisplayValue('cc')}
						</td>
					</tr>
				{/if}
				{if !empty($RECORD_MODEL->getDisplayValue('bcc'))}
					<tr>
						<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_BCC'|t:$QUALIFIED_MODULE}  </label></td>
						<td class="{$WIDTHTYPE} col-md-8">
							{$RECORD_MODEL->getDisplayValue('bcc')}
						</td>
					</tr>
				{/if}
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_SUBJECT'|t:$QUALIFIED_MODULE}  </label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('subject')}
					</td>
				</tr>
				{if !empty($RECORD_MODEL->getDisplayValue('attachments'))}
					<tr>
						<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_ATTACHMENTS'|t:$QUALIFIED_MODULE}  </label></td>
						<td class="{$WIDTHTYPE} col-md-8">
							{$RECORD_MODEL->getDisplayValue('attachments')}
						</td>
					</tr>
				{/if}
				<tr>
					<td class="{$WIDTHTYPE} col-md-3" ><label class="pull-right">{'LBL_CONTENT'|t:$QUALIFIED_MODULE}  </label></td>
					<td class="{$WIDTHTYPE} col-md-8">
						{$RECORD_MODEL->getDisplayValue('content')}
					</td>
				</tr>
			</tbody>
		</table>
		{else}
			<div class="alert alert-block alert-info fade in">
				<button type="button" class="close" data-dismiss="alert">×</button>
				<strong> <p>{'LBL_EMAIL_WAS_SENT'|t:$QUALIFIED_MODULE}</p> </strong>
				<a class="btn btn-info" href="{$MODULE_MODEL->getDefaultUrl()}">{'LBL_BACK'|t:$QUALIFIED_MODULE}</a>
			</div>	
		{/if}
	</div>
	{strip}
