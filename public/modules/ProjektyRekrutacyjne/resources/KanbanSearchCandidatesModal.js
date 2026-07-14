'use strict';

jQuery(document).off('click.kanbanSearchCandidates', '.js-kanban-search-candidates-submit');
jQuery(document).on('click.kanbanSearchCandidates', '.js-kanban-search-candidates-submit', function (event) {
	event.preventDefault();
	const $form = jQuery('#kanbanSearchCandidatesForm');
	if (!$form.length) {
		return;
	}

	const projectId = $form.data('project-id');
	const skillsRaw = jQuery.trim($form.find('[name="cv_skills"]').val());
	if (!projectId) {
		return;
	}
	if (!skillsRaw || /^\s*(AND|OR|\(|\))\s*$/i.test(skillsRaw)) {
		Vtiger_Helper_Js.showPnotify({
			text: app.vtranslate('LBL_KANBAN_CV_SKILLS_REQUIRED', 'ProjektyRekrutacyjne'),
			type: 'error'
		});
		return;
	}

	app.hideModalWindow();

	const detailInstance = Vtiger_Detail_Js.getInstance();
	if (detailInstance && typeof detailInstance.openKanbanPickCandidatesModal === 'function') {
		detailInstance.openKanbanPickCandidatesModal(projectId, skillsRaw);
	}
});

jQuery(function () {
	window.ProjektyRekrutacyjne_KanbanCvSkillsQueryStorage.prefillForm('#kanbanSearchCandidatesForm');
});

jQuery(document).off('keydown.kanbanSearchCandidates').on('keydown.kanbanSearchCandidates', function (event) {
	if (event.key !== 'Enter' || !jQuery('#kanbanSearchCandidatesForm').length) {
		return;
	}
	if (jQuery(event.target).closest('#kanbanSearchCandidatesForm').length) {
		event.preventDefault();
		jQuery('.js-kanban-search-candidates-submit').trigger('click');
	}
});
