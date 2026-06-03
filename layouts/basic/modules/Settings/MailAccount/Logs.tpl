{strip}
{extends file="MainLayout.tpl"|@vtemplate_path}
{block name="content"}
<div class="mainContainer"><div class="contentsDiv">
	<h3>{"LBL_LOGS"|t:$QUALIFIED_MODULE}</h3>
	<table class="table table-bordered">
		<thead><tr><th>{"LBL_DATE"|t:"Vtiger"}</th><th>Level</th><th>Action</th><th>Message</th></tr></thead>
		<tbody>
		{foreach from=$LOGS item=LOG}
			<tr>
				<td>{$LOG.created_at}</td>
				<td>{$LOG.level}</td>
				<td>{$LOG.action}</td>
				<td>{$LOG.message|escape}</td>
			</tr>
		{foreachelse}
			<tr><td colspan="4">{"LBL_NO_RECORDS"|t:"Vtiger"}</td></tr>
		{/foreach}
		</tbody>
	</table>
</div></div>
{/block}
{/strip}
