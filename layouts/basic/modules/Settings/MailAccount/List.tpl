{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}
{block name="content"}
<div class="mainContainer">
	<div class="contentsDiv">
		<div class="row widget_header">
			<div class="col-xs-12">
				{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			</div>
		</div>
		<div class="pull-right marginBottom10px">
			<a class="btn btn-default" href="index.php?module=MailAccount&parent=Settings&view=Logs">
				{"LBL_LOGS"|t:$QUALIFIED_MODULE}
			</a>
			<a class="btn btn-primary" href="index.php?module=MailAccount&parent=Settings&view=Edit">
				<span class="glyphicon glyphicon-plus"></span> {"LBL_ADD_RECORD"|t:$QUALIFIED_MODULE}
			</a>
		</div>
		<table class="table table-bordered listViewEntriesTable">
			<thead>
				<tr>
					<th>{"LBL_NAME"|t:$QUALIFIED_MODULE}</th>
					<th>{"LBL_KIND"|t:$QUALIFIED_MODULE}</th>
					<th>{"LBL_USERNAME"|t:$QUALIFIED_MODULE}</th>
					<th>{"LBL_ACTIVE"|t:$QUALIFIED_MODULE}</th>
					<th>{"LBL_LAST_SCAN"|t:$QUALIFIED_MODULE}</th>
					<th>{"LBL_STATUS"|t:$QUALIFIED_MODULE}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$ACCOUNTS item=ACCOUNT}
					<tr>
						<td>{$ACCOUNT.name}</td>
						<td>{$ACCOUNT.kind}</td>
						<td>{$ACCOUNT.username}</td>
						<td>{if $ACCOUNT.active eq 1}{"LBL_YES"|t:"Vtiger"}{else}{"LBL_NO"|t:"Vtiger"}{/if}</td>
						<td>{$ACCOUNT.last_scan_at|default:'-'}</td>
						<td>
							{if $ACCOUNT.last_scan_status eq 'ok'}<span class="label label-success">OK</span>
							{elseif $ACCOUNT.last_scan_status eq 'error'}<span class="label label-danger">{$ACCOUNT.consecutive_failures|default:0}</span>
							{elseif $ACCOUNT.last_scan_status eq 'disabled'}<span class="label label-default">off</span>
							{else}-{/if}
						</td>
						<td>
							<a class="btn btn-xs btn-info" href="index.php?module=MailAccount&parent=Settings&view=Edit&record={$ACCOUNT.id}">
								{"LBL_EDIT"|t:$QUALIFIED_MODULE}
							</a>
						</td>
					</tr>
				{foreachelse}
					<tr><td colspan="7">{"LBL_NO_RECORDS"|t:$QUALIFIED_MODULE}</td></tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>
{/block}
{/strip}
