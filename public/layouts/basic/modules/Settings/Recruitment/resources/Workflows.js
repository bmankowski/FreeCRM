/**
 * FreeCRM - Recruitment relation workflows matrix (Settings)
 */

'use strict';

jQuery.Class('Settings_Recruitment_Workflows_Js', {}, {
	buildWorkflowPopoverContent: function (workflows) {
		if (!workflows || !workflows.length) {
			return '';
		}
		const items = workflows.map(function (workflow) {
			const summary = jQuery('<div>').text(workflow.summary || '').html();
			const url = workflow.editUrl || '#';
			return '<li><a href="' + url + '">' + summary + '</a></li>';
		});
		return '<ul class="list-unstyled mb-0">' + items.join('') + '</ul>';
	},
	registerWorkflowPopovers: function () {
		const container = jQuery('#recruitmentWorkflowsContainer');
		const thisInstance = this;
		container.find('.js-workflow-badge').each(function () {
			const $badge = jQuery(this);
			let workflows = [];
			try {
				workflows = JSON.parse($badge.attr('data-workflows') || '[]');
			} catch (e) {
				workflows = [];
			}
			$badge.popover({
				html: true,
				trigger: 'focus',
				placement: 'auto',
				container: 'body',
				title: app.vtranslate('LBL_WORKFLOW_POPOVER_TITLE', 'Settings:Recruitment'),
				content: thisInstance.buildWorkflowPopoverContent(workflows)
			});
		});
		container.on('click', '.js-workflow-badge', function (event) {
			event.preventDefault();
			jQuery(this).focus();
		});
	},
	registerEvents: function () {
		this.registerWorkflowPopovers();
	}
});

jQuery(document).ready(function () {
	const instance = new Settings_Recruitment_Workflows_Js();
	instance.registerEvents();
});
