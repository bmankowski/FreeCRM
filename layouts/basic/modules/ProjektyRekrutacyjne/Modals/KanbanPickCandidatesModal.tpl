{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanPickCandidatesModal.tpl -->
<div class="modal-header">
	<h3 class="modal-title">{"LBL_KANBAN_PICK_CANDIDATES_TITLE"|t:$MODULE_NAME}</h3>
</div>
<div class="modal-body kanban-pick-candidates">
	<input type="hidden" id="kanbanPickProjectId" value="{$PROJECT_ID}"/>
	<input type="hidden" id="kanbanPickCvSkills" value="{$CV_SKILLS|escape:'html'}"/>
	<style>
		{literal}
		.kanban-pick-candidates__split {
			display: flex;
			flex-direction: row;
			flex-wrap: nowrap;
			align-items: stretch;
			width: 100%;
			height: calc(85vh - 180px);
			min-height: 420px;
			max-height: calc(85vh - 180px);
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
