'use strict';

Vtiger_Popup_Js('Candidates_Popup_Js', {}, {
	getCvSkillsFilter: function () {
		const vars = this.getVarFromUrl();
		if (!vars.cv_skills) {
			return '';
		}
		return decodeURIComponent(String(vars.cv_skills).replace(/\+/g, ' '));
	},

	getCompleteParams: function () {
		const params = this._super();
		const cvSkills = this.getCvSkillsFilter();
		if (cvSkills !== '') {
			params.cv_skills = cvSkills;
		}
		return params;
	}
});
