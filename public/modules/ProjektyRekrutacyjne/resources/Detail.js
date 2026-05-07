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
			$('.candidate').on('click', function () {
				focusOnCandidate($(this));
			});
			$(document).on('keydown', function (event) {
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
				const key = JSON.stringify(list);
				if (key === lastSerialized) {
					return;
				}
				lastSerialized = key;
				console.log('[ProjektyRekrutacyjne] Załadowani kandydaci (' + list.length + '):', list);
				if (list.length) {
					console.table(list);
				}
			};
			setTimeout(collectAndLog, 0);
			setTimeout(collectAndLog, 400);
			setTimeout(collectAndLog, 1200);
		},
		// Listens for drag and drop events on td elements with class candidate_status
		candidatesDragAndDropSupport: function () {
			let sourceStatus = null;
			let candidateId = null;

			$(document).off('dragstart.projektyKanban dragend.projektyKanban drop.projektyKanban dragover.projektyKanban dragenter.projektyKanban');

			$(document).on('dragstart.projektyKanban', '.candidate', function (e) {
				candidateId = $(this).attr('data-candidate-id');
				sourceStatus = $(this).closest('.candidate-status').attr('data-value');
				const ev = e.originalEvent;
				if (ev && ev.dataTransfer) {
					ev.dataTransfer.setData('text/plain', String(candidateId));
					ev.dataTransfer.effectAllowed = 'move';
				}
			});

			$(document).on('dragend.projektyKanban', '.candidate', function () {
				sourceStatus = null;
				candidateId = null;
			});

			$(document).on('drop.projektyKanban', '.candidate-status', function (event) {
				event.preventDefault();
				const destinationStatus = $(this).attr('data-value');
				if (sourceStatus === destinationStatus) {
					return;
				}
				const projectId = $(this).closest('.summaryWidgetContainer').find('.project-id').val() || $('.project-id').val();
				changeCandidateStatus(projectId, candidateId, sourceStatus, destinationStatus);
			});

			$(document).on('dragover.projektyKanban dragenter.projektyKanban', '.candidate-status', function (event) {
				event.preventDefault();
				const ev = event.originalEvent;
				if (ev && ev.dataTransfer) {
					ev.dataTransfer.dropEffect = 'move';
				}
			});
			function changeCandidateStatus (projectId, candidateId, sourceStatus, destinationStatus) {
				if(!projectId || !candidateId || !sourceStatus || !destinationStatus) {
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
					var result = data.result || {};
					var ok = data.success === true && result.success === true;
					if (ok) {
						const candidateElement = $(`.candidate[data-candidate-id="${candidateId}"]`);
						const candidateNewParent = $(`.candidate-status[data-value="${destinationStatus}"]`);
						const candidateParent = candidateElement.parent();
						candidateNewParent.append(candidateElement.first());
						if(candidateParent.length > 1) {
							candidateParent.each(function(index, element) {
								$(element).find(candidateElement).remove();
							});
						}
					} else {
						var errMsg = (result.message) || (data.error && data.error.message) || 'PLL_ACCEPTANCE_FAILED';
						Vtiger_Helper_Js.showPnotify({text: errMsg, type: 'error'});
					}
				}).fail(function () {
					Vtiger_Helper_Js.showPnotify({text: 'PLL_ACCEPTANCE_FAILED', type: 'error'});
				});
			}
		},

		registerEvents: function () {
			this._super();
			this.candidatesSupport();
			this.candidatesDragAndDropSupport();
			this.logLoadedCandidatesToConsole();
		}
	}
);
