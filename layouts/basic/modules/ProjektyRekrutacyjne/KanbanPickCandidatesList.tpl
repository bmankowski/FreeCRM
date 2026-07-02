{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/KanbanPickCandidatesList.tpl -->
<input type="hidden" class="js-kanban-pick-page-number" value="{$PAGE_NUMBER|default:1}"/>
<input type="hidden" class="js-kanban-pick-page-count" value="{$PAGE_COUNT|default:1}"/>
<input type="hidden" class="js-kanban-pick-total-count" value="{$LISTVIEW_COUNT|default:0}"/>
{if empty($ENTRIES)}
	<p class="kanban-pick-candidates__empty">{"LBL_KANBAN_PICK_CANDIDATES_EMPTY"|t:$MODULE_NAME}</p>
{else}
	<table class="table table-bordered table-sm listViewEntriesTable kanban-pick-candidates__table">
		<thead>
		<tr>
			<th class="narrow"><input type="checkbox" class="js-kanban-pick-select-page" title="{"LBL_SELECT_ALL_CURRENTPAGE"|t:"Vtiger"}"/></th>
			<th>{"LBL_KANBAN_PICK_COL_NAME"|t:$MODULE_NAME}</th>
			<th>{"LBL_KANBAN_PICK_COL_PHONE"|t:$MODULE_NAME}</th>
		</tr>
		</thead>
		<tbody>
		{foreach from=$ENTRIES item=ENTRY}
			<tr class="listViewEntries js-kanban-pick-row" data-id="{$ENTRY->getId()}" data-name="{$ENTRY->get('name')|escape:'html'}">
				<td class="narrow">
					<input type="checkbox" class="entryCheckBox js-kanban-pick-checkbox" title="{"LBL_SELECT_RECORD"|t:"Vtiger"}"/>
				</td>
				<td class="js-kanban-pick-name">{$ENTRY->get('name')|escape}</td>
				<td>{$ENTRY->get('phone')|escape}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
{/if}
<!-- /layouts/basic/modules/ProjektyRekrutacyjne/KanbanPickCandidatesList.tpl -->
{/strip}
