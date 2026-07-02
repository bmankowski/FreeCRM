{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanPickCandidatesModal.tpl -->
<div class="modal-header">
	<h3 class="modal-title">{"LBL_KANBAN_PICK_CANDIDATES_TITLE"|t:$MODULE_NAME}</h3>
</div>
<div class="modal-body kanban-pick-candidates">
	<input type="hidden" id="kanbanPickProjectId" value="{$PROJECT_ID}"/>
	<input type="hidden" id="kanbanPickCvSkills" value="{$CV_SKILLS|escape:'html'}"/>
	<div class="kanban-pick-candidates__split relatedContents">
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
			<iframe class="listPreviewframe js-kanban-pick-cv-frame" title="{"LBL_KANBAN_CV_PREVIEW"|t:$MODULE_NAME}"></iframe>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-success js-kanban-pick-candidates-add">{"LBL_KANBAN_PICK_CANDIDATES_ADD"|t:$MODULE_NAME}</button>
	<button type="button" class="btn btn-warning" data-dismiss="modal">{"LBL_CANCEL"|t:"Vtiger"}</button>
</div>
<!-- /layouts/basic/modules/ProjektyRekrutacyjne/Modals/KanbanPickCandidatesModal.tpl -->
{/strip}
