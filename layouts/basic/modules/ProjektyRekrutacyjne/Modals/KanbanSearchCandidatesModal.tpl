{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanSearchCandidatesModal.tpl -->
<div class="modal-header">
	<h3 class="modal-title">{"LBL_KANBAN_SEARCH_CANDIDATES_TITLE"|t:$MODULE_NAME}</h3>
</div>
<div class="modal-body">
	<form id="kanbanSearchCandidatesForm" data-project-id="{$PROJECT_ID}">
		<div class="form-group">
			<label for="kanbanCvSkillsInput">{"LBL_KANBAN_CV_SKILLS"|t:$MODULE_NAME}</label>
			<textarea id="kanbanCvSkillsInput"
					  class="form-control js-kanban-cv-skills-input"
					  name="cv_skills"
					  rows="4"
					  placeholder="{"LBL_KANBAN_CV_SKILLS_PLACEHOLDER"|t:$MODULE_NAME}"></textarea>
			<p class="help-block">{"LBL_KANBAN_CV_SKILLS_HINT"|t:$MODULE_NAME}</p>
		</div>
	</form>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-success js-kanban-search-candidates-submit">{"LBL_SEARCH"|t:"Vtiger"}</button>
	<button type="button" class="btn btn-warning" data-dismiss="modal">{"LBL_CANCEL"|t:"Vtiger"}</button>
</div>
<!-- /layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanSearchCandidatesModal.tpl -->
{/strip}
