/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
'use strict';

Vtiger_Detail_Js(
	'ProjektyRekrutacyjne_Detail_Js',
	{},
	{
		candidatesSupport: function () {
			$(document)
				.off('click.projektyCandidateOpen', '.candidate')
				.on('click.projektyCandidateOpen', '.candidate', function (event) {
					event.preventDefault();
					focusOnCandidate($(this));
				});
			$(document).off('keydown.projektyCandidateOpen').on('keydown.projektyCandidateOpen', function (event) {
				if (event.key === 'ArrowLeft') {
					const currentElement = $('.candidate-focused');
					const prevElement = currentElement.prev();
					if (prevElement.length > 0) {
						focusOnCandidate(prevElement);
					}
				} else if (event.key === 'ArrowRight') {
					const currentElement = $('.candidate-focused');
					const nextElement = currentElement.next();
					if (nextElement.length > 0) {
						focusOnCandidate(nextElement);
					}
				}
			});
			function focusOnCandidate(candidate) {
				let candidateActiveClass = 'candidate-focused';
				const candidateUrl = candidate.attr('datasrc');
				if (!candidateUrl) {
					return;
				}
				$('.candidate-focused').removeClass(candidateActiveClass);
				candidate.addClass(candidateActiveClass);
				window.open(candidateUrl, 'candidate-preview', 'width=800,height=600,scrollbars=yes');

			}
		},
		/**
		 * Wypisuje w konsoli listę kandydatów widocznych w kanbanie (widget podsumowania).
		 * Wywołanie jest powtarzane z opóźnieniem, bo markup może dojść po init widoku.
		 */
		logLoadedCandidatesToConsole: function () {
			let lastSerialized = '';
			const collectAndLog = function () {
				const list = [];
				$('.candidate').each(function () {
					const $el = $(this);
					list.push({
						id: $el.attr('data-candidate-id'),
						name: $.trim($el.text()),
						status: $el.closest('.candidate-status').attr('data-value') || ''
					});
				});
				if (!list.length) {
					return;
				}
				const key = JSON.stringify(list);
				if (key === lastSerialized) {
					return;
				}
				lastSerialized = key;
				console.log('[ProjektyRekrutacyjne] Loaded candidates (' + list.length + '):', list);
				console.table(list);
			};
			setTimeout(collectAndLog, 0);
			setTimeout(collectAndLog, 400);
			setTimeout(collectAndLog, 1200);
		},
		refreshUiAfterManualCandidatesAdd: function (result) {
			const statusManual = 'PPL_MANUALLY_ADDED';
			if (result && result.addedCandidates && result.addedCandidates.length) {
				result.addedCandidates.forEach(function (candidate) {
					const candidateId = String(candidate.id);
					const status = candidate.status || statusManual;
					if ($('.candidate[data-candidate-id="' + candidateId + '"]').length) {
						return;
					}
					const $col = $('.candidate-status[data-value="' + status + '"]').first();
					if (!$col.length) {
						return;
					}
					const $chip = $('<div class="candidate candidate-chip" draggable="true"></div>')
						.attr('datasrc', candidate.detailUrl || '')
						.attr('data-candidate-id', candidateId)
						.text(candidate.name || '');
					$col.append($chip);
				});
			}
			if (typeof this.getRelatedModuleName === 'function'
				&& this.getRelatedModuleName() === 'Candidates'
				&& typeof this.loadRelatedList === 'function') {
				const tab = this.getSelectedTab();
				const params = { page: 1 };
				if (tab && tab.length && tab.data('label-key')) {
					params.tab_label = tab.data('label-key');
				}
				this.loadRelatedList(params);
			}
		},
		registerKanbanAddManualCandidate: function () {
			const thisInstance = this;
			$(document).off('click.projektyKanbanAddManual', '.js-kanban-add-manual-candidate');
			$(document).on('click.projektyKanbanAddManual', '.js-kanban-add-manual-candidate', function (event) {
				event.preventDefault();
				const $container = $(this).closest('.summaryWidgetContainer');
				const projectId = $container.find('.project-id').val() || app.getRecordId();
				if (!projectId) {
					return;
				}
				const cvBooleanQuery = $.trim($container.find('.js-cv-boolean-query').val() || '');
				const storage = window.ProjektyRekrutacyjne_KanbanCvSkillsQueryStorage;
				if (cvBooleanQuery && storage && storage.isPersistableQuery(cvBooleanQuery)) {
					thisInstance.openKanbanPickCandidatesModal(projectId, cvBooleanQuery);
					return;
				}
				const modalUrl = 'index.php?module=' + app.getModuleName()
					+ '&view=KanbanSearchCandidatesModal&projectId=' + encodeURIComponent(projectId);
				app.showModalWindow(null, modalUrl);
			});
		},
		openKanbanPickCandidatesModal: function (projectId, cvSkills) {
			if (!projectId) {
				return;
			}
			let modalUrl = 'index.php?module=' + app.getModuleName()
				+ '&view=KanbanPickCandidatesModal&projectId=' + encodeURIComponent(projectId);
			if (cvSkills) {
				modalUrl += '&cv_skills=' + encodeURIComponent(cvSkills);
			}
			app.showModalWindow(null, modalUrl);
		},
		submitManualCandidates: function (projectId, candidateIds) {
			const thisInstance = this;
			const params = {
				module: app.getModuleName(),
				action: 'AddManualCandidatesAjax',
				projectId: projectId,
				candidateIds: JSON.stringify(candidateIds)
			};
			return AppConnector.request(params).done(function (data) {
				if (data.success !== true) {
					const errMsg = (data.error && data.error.message)
						|| (data.result && data.result.message)
						|| app.vtranslate('PLL_ACCEPTANCE_FAILED', app.getModuleName());
					Vtiger_Helper_Js.showPnotify({ text: errMsg, type: 'error' });
					return;
				}
				const result = data.result || {};
				if (result.skipped && result.skipped.length) {
					const msg = app.vtranslate('LBL_MANUAL_CANDIDATES_SKIPPED', app.getModuleName())
						.replace('%s', result.skipped.length);
					Vtiger_Helper_Js.showPnotify({ text: msg, type: 'info' });
				}
				if (!result.success && (!result.added || !result.added.length)) {
					if (result.skipped && result.skipped.length) {
						return;
					}
					const msg = result.message
						? app.vtranslate(result.message, app.getModuleName())
						: app.vtranslate('PLL_NO_SUCH_RECORD', app.getModuleName());
					Vtiger_Helper_Js.showPnotify({ text: msg, type: 'error' });
					return;
				}
				thisInstance.refreshUiAfterManualCandidatesAdd(result);
			}).fail(function (_jqXHR, _textStatus, errorThrown) {
				Vtiger_Helper_Js.showPnotify({
					text: errorThrown || app.vtranslate('PLL_ACCEPTANCE_FAILED', app.getModuleName()),
					type: 'error'
				});
			});
		},
		registerProjectRelatedListController: function () {
			const thisInstance = this;
			const register = function () {
				if (typeof window.ProjektyRekrutacyjne_RelatedList_Js !== 'function') {
					return;
				}
				const relatedModuleName = thisInstance.getRelatedModuleName && thisInstance.getRelatedModuleName();
				if (relatedModuleName !== 'Candidates') {
					return;
				}
				const selectedTab = thisInstance.getSelectedTab();
				if (!selectedTab || !selectedTab.length) {
					return;
				}
				const relatedController = new window.ProjektyRekrutacyjne_RelatedList_Js(
					thisInstance.getRecordId(),
					app.getModuleName(),
					selectedTab,
					relatedModuleName
				);
				relatedController.registerRelatedEvents();
			};
			register();
			setTimeout(register, 0);
			setTimeout(register, 250);
		},
		// Listens for drag and drop events on td elements with class candidate_status
		candidatesDragAndDropSupport: function () {
			let sourceStatus = null;
			let candidateId = null;
			let draggedCandidate = null;

			const parseStatusTransitions = function ($container) {
				const raw = $container.find('.js-status-transitions').val();
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

			const clearDropHighlights = function ($container) {
				$container.find('.candidate-status').removeClass('drop-allowed drop-forbidden');
			};

			const applyDropHighlights = function ($container, fromStatus, cfg) {
				clearDropHighlights($container);
				if (!cfg.configured || !fromStatus) {
					return;
				}
				$container.find('.candidate-status').each(function () {
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

			$(document).off('dragstart.projektyKanban dragend.projektyKanban drop.projektyKanban dragover.projektyKanban dragenter.projektyKanban');

			$(document).on('dragstart.projektyKanban', '.candidate', function (e) {
				draggedCandidate = $(this);
				candidateId = draggedCandidate.attr('data-candidate-id');
				sourceStatus = draggedCandidate.closest('.candidate-status').attr('data-value');
				const $container = draggedCandidate.closest('.summaryWidgetContainer');
				const cfg = parseStatusTransitions($container);
				applyDropHighlights($container, sourceStatus, cfg);
				const ev = e.originalEvent;
				if (ev && ev.dataTransfer) {
					ev.dataTransfer.setData('text/plain', String(candidateId));
					ev.dataTransfer.effectAllowed = 'move';
				}
			});

			$(document).on('dragend.projektyKanban', '.candidate', function () {
				const $container = $(this).closest('.summaryWidgetContainer');
				clearDropHighlights($container);
				sourceStatus = null;
				candidateId = null;
				draggedCandidate = null;
			});

			$(document).on('drop.projektyKanban', '.candidate-status', function (event) {
				event.preventDefault();
				const destinationStatus = $(this).attr('data-value');
				if (sourceStatus === destinationStatus) {
					return;
				}
				const $container = $(this).closest('.summaryWidgetContainer');
				const cfg = parseStatusTransitions($container);
				clearDropHighlights($container);
				if (!isTransitionAllowed(cfg, sourceStatus, destinationStatus)) {
					showTransitionError('PLL_STATUS_TRANSITION_NOT_ALLOWED');
					return;
				}
				const projectId = $container.find('.project-id').val() || $('.project-id').val();
				changeCandidateStatus(projectId, candidateId, sourceStatus, destinationStatus, draggedCandidate);
			});

			$(document).on('dragover.projektyKanban dragenter.projektyKanban', '.candidate-status', function (event) {
				event.preventDefault();
				const ev = event.originalEvent;
				if (ev && ev.dataTransfer) {
					const $cell = $(this);
					ev.dataTransfer.dropEffect = $cell.hasClass('drop-forbidden') ? 'none' : 'move';
				}
			});

			function changeCandidateStatus(projectId, candidateId, sourceStatus, destinationStatus, $candidateElement) {
				if (!projectId || !candidateId || !sourceStatus || !destinationStatus) {
					console.error('Missing parameters for changeCandidateStatus');
					return;
				}
				const params = {
					module: app.getModuleName(),
					action: 'ChangeCandidateStatusManuallyAjax',
					candidateId: candidateId,
					projectId: projectId,
					sourceStatus: sourceStatus,
					destinationStatus: destinationStatus
				};
				AppConnector.request(params).done(function (data) {
					const result = data.result || {};
					const ok = data.success === true && result.success === true;
					if (ok) {
						const candidateElement = $candidateElement && $candidateElement.length
							? $candidateElement
							: $(`.candidate[data-candidate-id="${candidateId}"]`);
						const candidateNewParent = $(`.candidate-status[data-value="${destinationStatus}"]`);
						const candidateParent = candidateElement.parent();
						candidateNewParent.append(candidateElement.first());
						if (candidateParent.length > 1) {
							candidateParent.each(function (index, element) {
								$(element).find(candidateElement).remove();
							});
						}
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
					} else {
						const errMsg = (result.message) || (data.error && data.error.message) || 'PLL_ACCEPTANCE_FAILED';
						showTransitionError(errMsg);
					}
				}).fail(function () {
					showTransitionError('PLL_ACCEPTANCE_FAILED');
				});
			}
		},

		registerJobAdvertisementLinkCopy: function () {
			const container = '#ProjektyRekrutacyjne_detailView_fieldValue_job_advertisement_links';
			const selector = container + ' .job-ad-link-copy, ' + container + ' table tr td:nth-child(3)';
			$(document)
				.off('click.projektyJobAdCopy', selector)
				.on('click.projektyJobAdCopy', selector, function (event) {
					event.preventDefault();
					const $target = $(this);
					const href = $target.is('a')
						? $target.attr('href')
						: $target.closest('tr').find('td:nth-child(2) a').attr('href');
					if (!href || !navigator.clipboard || !navigator.clipboard.writeText) {
						return;
					}
					navigator.clipboard.writeText(href).then(function () {
						$target.addClass('is-copied');
						setTimeout(function () {
							$target.removeClass('is-copied');
						}, 1200);
						Vtiger_Helper_Js.showPnotify({
							text: app.vtranslate('JS_NOTIFY_COPY_TEXT'),
							type: 'success'
						});
					});
				});
		},

		registerEvents: function () {
			this._super();
			this.candidatesSupport();
			this.candidatesDragAndDropSupport();
			this.registerKanbanAddManualCandidate();
			this.logLoadedCandidatesToConsole();
			this.registerProjectRelatedListController();
			this.registerJobAdvertisementLinkCopy();
		}
	}
);
