{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanSearchCandidatesModal.tpl -->
<div class="modal-header">
	<h3 class="modal-title">{"LBL_KANBAN_SEARCH_CANDIDATES_TITLE"|t:$MODULE_NAME}</h3>
</div>
<div class="modal-body">
	<form id="kanbanSearchCandidatesForm" data-project-id="{$PROJECT_ID}">
		{include file='partials/KanbanCvSkillsQueryField.tpl'|@vtemplate_path:$MODULE_NAME
			FIELD_ID='kanbanCvSkillsInput'
			FIELD_VALUE=''
			ROWS=4}
	</form>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-success js-kanban-search-candidates-submit">{"LBL_SEARCH"|t:"Vtiger"}</button>
	<button type="button" class="btn btn-warning" data-dismiss="modal">{"LBL_CANCEL"|t:"Vtiger"}</button>
</div>
<!-- /layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanSearchCandidatesModal.tpl -->
{/strip}
