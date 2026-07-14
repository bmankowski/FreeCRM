'use strict';

window.ProjektyRekrutacyjne_KanbanCvSkillsQueryStorage = {
	getStorageKey: function () {
		const userId = app.getMainParams('current_user_id');
		return 'freecrm.kanbanCvSkillsLastQuery.' + String(userId || 0);
	},

	isPersistableQuery: function (raw) {
		const trimmed = jQuery.trim(String(raw || ''));
		return trimmed !== '' && !/^\s*(AND|OR|\(|\))\s*$/i.test(trimmed);
	},

	save: function (raw) {
		if (!this.isPersistableQuery(raw)) {
			return;
		}
		try {
			window.localStorage.setItem(this.getStorageKey(), jQuery.trim(String(raw)));
		} catch (_e) {
		}
	},

	load: function () {
		try {
			const stored = window.localStorage.getItem(this.getStorageKey());
			return stored != null ? String(stored) : '';
		} catch (_e) {
			return '';
		}
	},

	prefillForm: function (formSelector) {
		const $form = jQuery(formSelector);
		if (!$form.length) {
			return;
		}
		const $input = $form.find('[name="cv_skills"]');
		if (!$input.length || jQuery.trim($input.val() || '') !== '') {
			return;
		}
		const stored = this.load();
		if (stored) {
			$input.val(stored);
		}
	}
};
