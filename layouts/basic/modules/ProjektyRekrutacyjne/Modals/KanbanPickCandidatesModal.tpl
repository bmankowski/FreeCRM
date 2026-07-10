{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanPickCandidatesModal.tpl -->
<div class="modal-header">
	<h3 class="modal-title">{"LBL_KANBAN_PICK_CANDIDATES_TITLE"|t:$MODULE_NAME}</h3>
</div>
<div class="modal-body kanban-pick-candidates">
	<input type="hidden" id="kanbanPickProjectId" value="{$PROJECT_ID}"/>
	<form id="kanbanPickCandidatesSearchForm" class="kanban-pick-candidates__search">
		<div class="row">
			<div class="col-sm-9">
				{include file='partials/KanbanCvSkillsQueryField.tpl'|@vtemplate_path:$MODULE_NAME
					FIELD_ID='kanbanPickCvSkillsInput'
					FIELD_VALUE=$CV_SKILLS
					ROWS=3}
			</div>
			<div class="col-sm-3 kanban-pick-candidates__search-actions">
				<button type="button" class="btn btn-success btn-block js-kanban-pick-candidates-search">{"LBL_SEARCH"|t:"Vtiger"}</button>
			</div>
		</div>
	</form>
	<div class="kanban-pick-candidates__split">
		<div class="c-list-preview js-kanban-pick-list">
			<div class="kanban-pick-candidates__list-toolbar">
				<span class="js-kanban-pick-count text-muted"></span>
				<div class="kanban-pick-candidates__pager js-kanban-pick-pager"></div>
			</div>
			<div class="c-list-preview__content js-kanban-pick-list-body">
				<div class="kanban-pick-candidates__loading">{"LBL_LOADING"|t:"Vtiger"}</div>
			</div>
		</div>
		<div class="c-list-preview-resizer js-kanban-pick-resizer" aria-hidden="true"></div>
		<div class="c-detail-preview js-kanban-pick-preview">
			<iframe class="js-kanban-pick-cv-frame" title="{"LBL_KANBAN_CV_PREVIEW"|t:$MODULE_NAME}"></iframe>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-success js-kanban-pick-candidates-add">{"LBL_KANBAN_PICK_CANDIDATES_ADD"|t:$MODULE_NAME}</button>
	<button type="button" class="btn btn-warning" data-dismiss="modal">{"LBL_CANCEL"|t:"Vtiger"}</button>
</div>
<!-- /layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanPickCandidatesModal.tpl -->
{/strip}
