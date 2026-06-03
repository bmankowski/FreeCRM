{strip}
<table class="table table-bordered listViewEntriesTable">
	<thead>
		<tr>
			<th>{"LBL_INBOUND"|t:"Mail"} / {"LBL_OUTBOUND"|t:"Mail"}</th>
			<th>{"Subject"|t:"Vtiger"}</th>
			<th>From</th>
			<th>{"Date"|t:"Vtiger"}</th>
		</tr>
	</thead>
	<tbody>
		{foreach from=$MAIL_ENTRIES item=ROW}
			<tr>
				<td>{if $ROW.direction eq 'in'}{"LBL_INBOUND"|t:"Mail"}{else}{"LBL_OUTBOUND"|t:"Mail"}{/if}</td>
				<td><a href="index.php?module=Mail&view=Detail&record={$ROW.id}">{$ROW.subject|escape}</a></td>
				<td>{$ROW.from_email|escape}</td>
				<td>{$ROW.date_sent}</td>
			</tr>
		{foreachelse}
			<tr><td colspan="4">{"LBL_NO_RECORDS"|t:"Vtiger"}</td></tr>
		{/foreach}
	</tbody>
</table>
{/strip}
