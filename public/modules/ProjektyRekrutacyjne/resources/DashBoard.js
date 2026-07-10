'use strict';

Vtiger_DashBoard_Js('ProjektyRekrutacyjne_DashBoard_Js', {}, {
	registerCandidateClick: function () {
		$(document).off('click.projektyDashboardCandidate', '.recruitment-projects-dashboard .candidate');
		$(document).on('click.projektyDashboardCandidate', '.recruitment-projects-dashboard .candidate', function (event) {
			event.preventDefault();
			const url = $(this).attr('datasrc');
			if (url) {
				window.open(url, 'candidate-preview', 'width=800,height=600,scrollbars=yes');
			}
		});
	},

	registerProjectsDashboardDragAndDrop: function () {
		let sourceStatus = null;
		let candidateId = null;
		let draggedCandidate = null;

		const $dashboard = $('.recruitment-projects-dashboard');
		if (!$dashboard.length) {
			return;
		}

		const parseStatusTransitions = function () {
			const raw = $dashboard.find('.js-status-transitions').val();
			if (!raw) {
				return { configured: false, transitions: {} };
			}
			try {
				return JSON.parse(raw);
			} catch (_e) {
				return { configured: false, transitions: {} };
			}
		};

		const isTransitionAllowed = function (cfg, from, to) {
			if (!cfg || !cfg.configured) {
				return true;
			}
			const allowed = cfg.transitions && cfg.transitions[from];
			return Array.isArray(allowed) && allowed.indexOf(to) !== -1;
		};

		const clearDropHighlights = function ($scope) {
			$scope.find('.candidate-status').removeClass('drop-allowed drop-forbidden');
		};

		const applyDropHighlights = function ($row, fromStatus, cfg) {
			clearDropHighlights($row);
			if (!cfg.configured || !fromStatus) {
				return;
			}
			$row.find('.candidate-status').each(function () {
				const $cell = $(this);
				const toStatus = $cell.attr('data-value');
				if (toStatus === fromStatus) {
					return;
				}
				if (isTransitionAllowed(cfg, fromStatus, toStatus)) {
					$cell.addClass('drop-allowed');
				} else {
					$cell.addClass('drop-forbidden');
				}
			});
		};

		const showTransitionError = function (messageKey) {
			const text = app.vtranslate(messageKey || 'PLL_STATUS_TRANSITION_NOT_ALLOWED');
			Vtiger_Helper_Js.showPnotify({ text: text, type: 'error' });
		};

		const changeCandidateStatus = function (projectId, candidateIdValue, source, destination, $candidateElement, $row) {
			if (!projectId || !candidateIdValue || !source || !destination) {
				return;
			}
			const params = {
				module: app.getModuleName(),
				action: 'ChangeCandidateStatusManuallyAjax',
				candidateId: candidateIdValue,
				projectId: projectId,
				sourceStatus: source,
				destinationStatus: destination
			};
			AppConnector.request(params).done(function (data) {
				const result = data.result || {};
				const ok = data.success === true && result.success === true;
				if (!ok) {
					const errMsg = result.message || (data.error && data.error.message) || 'PLL_ACCEPTANCE_FAILED';
					showTransitionError(errMsg);
					return;
				}

				const $chip = $candidateElement && $candidateElement.length
					? $candidateElement
					: $row.find('.candidate[data-candidate-id="' + candidateIdValue + '"]');
				const $destCell = $row.find('.candidate-status[data-value="' + destination + '"]');
				$destCell.append($chip.first());

				const autoSend = result.autoSend;
				if (autoSend && (autoSend.sent > 0 || autoSend.failed > 0)) {
					let toastText;
					let toastType = 'success';
					if (autoSend.failed > 0 && autoSend.sent > 0) {
						toastText = app.vtranslate('JS_TRANSITION_MAIL_AUTO_PARTIAL', app.getModuleName());
						toastType = 'warning';
					} else if (autoSend.failed > 0) {
						toastText = app.vtranslate('JS_TRANSITION_MAIL_AUTO_FAILED', app.getModuleName());
						toastType = 'error';
					} else {
						toastText = app.vtranslate('JS_TRANSITION_MAIL_AUTO_SENT', app.getModuleName());
					}
					Vtiger_Helper_Js.showPnotify({ text: toastText, type: toastType });
				}

				const mailPrompt = result.mailPrompt;
				if (mailPrompt && mailPrompt.templateIds && mailPrompt.templateIds.length
					&& typeof Vtiger_Index_Js !== 'undefined'
					&& typeof Vtiger_Index_Js.triggerSendEmailModal === 'function') {
					Vtiger_Index_Js.triggerSendEmailModal({
						module: 'Candidates',
						selectedIds: [mailPrompt.candidateId],
						sourceModule: app.getModuleName(),
						sourceRecord: mailPrompt.projectId,
						view: 'IndividualSendMailModal',
						templateIds: mailPrompt.templateIds
					});
				}
			}).fail(function () {
				showTransitionError('PLL_ACCEPTANCE_FAILED');
			});
		};

		$(document).off('dragstart.projektyDashboardKanban dragend.projektyDashboardKanban drop.projektyDashboardKanban dragover.projektyDashboardKanban dragenter.projektyDashboardKanban');

		$(document).on('dragstart.projektyDashboardKanban', '.recruitment-projects-dashboard .candidate', function (e) {
			draggedCandidate = $(this);
			candidateId = draggedCandidate.attr('data-candidate-id');
			sourceStatus = draggedCandidate.closest('.candidate-status').attr('data-value');
			const $row = draggedCandidate.closest('tr[data-project-id]');
			const cfg = parseStatusTransitions();
			applyDropHighlights($row, sourceStatus, cfg);
			const ev = e.originalEvent;
			if (ev && ev.dataTransfer) {
				ev.dataTransfer.setData('text/plain', String(candidateId));
				ev.dataTransfer.effectAllowed = 'move';
			}
		});

		$(document).on('dragend.projektyDashboardKanban', '.recruitment-projects-dashboard .candidate', function () {
			const $row = $(this).closest('tr[data-project-id]');
			clearDropHighlights($row);
			sourceStatus = null;
			candidateId = null;
			draggedCandidate = null;
		});

		$(document).on('drop.projektyDashboardKanban', '.recruitment-projects-dashboard .candidate-status', function (event) {
			event.preventDefault();
			const $destCell = $(this);
			const $row = $destCell.closest('tr[data-project-id]');
			const destinationStatus = $destCell.attr('data-value');
			if (sourceStatus === destinationStatus) {
				return;
			}
			const cfg = parseStatusTransitions();
			clearDropHighlights($row);
			if (!isTransitionAllowed(cfg, sourceStatus, destinationStatus)) {
				showTransitionError('PLL_STATUS_TRANSITION_NOT_ALLOWED');
				return;
			}
			const projectId = $row.data('project-id');
			changeCandidateStatus(projectId, candidateId, sourceStatus, destinationStatus, draggedCandidate, $row);
		});

		$(document).on('dragover.projektyDashboardKanban dragenter.projektyDashboardKanban', '.recruitment-projects-dashboard .candidate-status', function (event) {
			event.preventDefault();
			const ev = event.originalEvent;
			if (ev && ev.dataTransfer) {
				const $cell = $(this);
				ev.dataTransfer.dropEffect = $cell.hasClass('drop-forbidden') ? 'none' : 'move';
			}
		});
	},

	registerEvents: function () {
		this.registerProjectsDashboardDragAndDrop();
		this.registerCandidateClick();
	}
});
