{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanPickCandidatesModal.tpl -->
<div class="modal-header">
	<h3 class="modal-title">{"LBL_KANBAN_PICK_CANDIDATES_TITLE"|t:$MODULE_NAME}</h3>
</div>
<div class="modal-body kanban-pick-candidates">
	<input type="hidden" id="kanbanPickProjectId" value="{$PROJECT_ID}"/>
	<form id="kanbanPickCandidatesSearchForm" class="kanban-pick-candidates__search">
		<div class="form-group">
			<label for="kanbanPickCvSkillsInput">{"LBL_KANBAN_CV_SKILLS"|t:$MODULE_NAME}</label>
			<textarea id="kanbanPickCvSkillsInput"
					  class="form-control js-kanban-cv-skills-input"
					  name="cv_skills"
					  rows="3"
					  placeholder="{"LBL_KANBAN_CV_SKILLS_PLACEHOLDER"|t:$MODULE_NAME}">{$CV_SKILLS|escape:'html'}</textarea>
			<p class="help-block">{"LBL_KANBAN_CV_SKILLS_HINT"|t:$MODULE_NAME}</p>
		</div>
		<div class="kanban-pick-candidates__search-actions">
			<button type="button" class="btn btn-success js-kanban-pick-candidates-search">{"LBL_SEARCH"|t:"Vtiger"}</button>
		</div>
	</form>
	<style>
		{literal}
		.kanban-pick-candidates__split {
			display: flex;
			flex-direction: row;
			flex-wrap: nowrap;
			align-items: stretch;
			width: 100%;
			height: calc(85vh - 300px);
			min-height: 360px;
			max-height: calc(85vh - 300px);
			overflow: hidden;
		}
		.kanban-pick-candidates__split > .c-list-preview,
		.kanban-pick-candidates__split > .c-list-preview-resizer,
		.kanban-pick-candidates__split > .c-detail-preview {
			float: none;
			height: 100%;
			min-height: 0;
		}
		.kanban-pick-candidates__split > .c-detail-preview {
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}
		.kanban-pick-candidates__split > .c-detail-preview > .js-kanban-pick-cv-frame {
			flex: 1 1 auto;
			min-height: 0;
			width: 100%;
			border: 0;
		}
		{/literal}
	</style>
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
