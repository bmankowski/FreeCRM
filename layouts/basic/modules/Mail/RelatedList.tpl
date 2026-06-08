{strip}
<table class="table table-bordered listViewEntriesTable">
	<thead>
		<tr>
			<th>{"LBL_INBOUND"|t:"Mail"} / {"LBL_OUTBOUND"|t:"Mail"}</th>
			<th>{"Subject"|t:"Vtiger"}</th>
			<th>From</th>
			<th>{"Date"|t:"Vtiger"}</th>
			<th>{"LBL_MAIL_SEND_STATUS"|t:"Mail"}</th>
			<th>{"LBL_MAIL_OPENED_AT"|t:"Mail"}</th>
		</tr>
	</thead>
	<tbody>
		{foreach from=$MAIL_ENTRIES item=ROW}
			<tr>
				<td>{if $ROW.direction eq 'in'}{"LBL_INBOUND"|t:"Mail"}{else}{"LBL_OUTBOUND"|t:"Mail"}{/if}</td>
				<td><a href="index.php?module=Mail&amp;view=Detail&amp;record={$ROW.id}&amp;sourceModule={$MODULE|escape:'url'}&amp;sourceRecord={$PARENT_RECORD}">{$ROW.subject|escape}</a></td>
				<td>{$ROW.from_email|escape}</td>
				<td>{$ROW.date_sent}</td>
				<td>
					{if $ROW.direction eq 'out'}
						{if $ROW.send_status eq 'failed'}{"LBL_MAIL_SEND_STATUS_FAILED"|t:"Mail"}
						{elseif $ROW.send_status eq 'prepared'}{"LBL_MAIL_SEND_STATUS_PREPARED"|t:"Mail"}
						{else}{"LBL_MAIL_SEND_STATUS_SENT"|t:"Mail"}{/if}
					{else}—{/if}
				</td>
				<td>
					{if $ROW.opened_at_display}{$ROW.opened_at_display|escape}{elseif $ROW.direction eq 'out'}—{else}—{/if}
				</td>
			</tr>
		{foreachelse}
			<tr><td colspan="6">{"LBL_NO_RECORDS"|t:"Vtiger"}</td></tr>
		{/foreach}
	</tbody>
</table>
{/strip}
