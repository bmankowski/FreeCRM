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
				let candidateActiveClass = 'candidate-focused bg-primary border-light text-white';
				const candidateUrl = candidate.attr('datasrc');
				$('.candidate-focused').removeClass(candidateActiveClass);
				candidate.addClass(candidateActiveClass);
				window.open(candidateUrl, 'candidate-preview', 'width=800,height=600,scrollbars=yes');

			}
		},
		// Listens for drag and drop events on td elements with class candidate_status
		candidatesDragAndDropSupport: function () {
			let sourceStatus = null;
			let candidateId = null;

			$('.candidate').on('dragstart', function () {
				sourceStatus = $(this).parent().attr('data-value');
				candidateId = $(this).attr('data-candidate-id');
			});

			$('.candidate-status').on('drop', function (event) {
				event.preventDefault();
				const destinationStatus = $(this).attr('data-value');
				if(sourceStatus === destinationStatus) {
					return;
				}
				const projectId = $('.project-id').val();
				changeCandidateStatus(projectId,candidateId,sourceStatus, destinationStatus);
			});

			$('.candidate-status').on('dragover', function (event) {
				event.preventDefault();
			});
			function changeCandidateStatus (projectId, candidateId, sourceStatus, destinationStatus) {
				if(!projectId || !candidateId || !sourceStatus || !destinationStatus) {
					console.error('Missing parameters for changeCandidateStatus');
					return
				}
				// console.log(`Changing status of candidate ${candidateId} from ${sourceStatus} to ${destinationStatus} in project ${projectId}`);
				const params = {
					module: app.getModuleName(),
					action: 'ChangeCandidateStatusManuallyAjax',
					candidateId: candidateId,
					projectId: projectId,
					sourceStatus: sourceStatus,
					destinationStatus: destinationStatus
				};
				AppConnector.request(params).done(function (data) {
					if (data.success) {
						//Find with jquery the candidate element and change its parent
						const candidateElement = $(`.candidate[data-candidate-id="${candidateId}"]`);
						const candidateNewParent= $(`.candidate-status[data-value="${destinationStatus}"]`);
						const candidateParent = candidateElement.parent();
						// if candidateParent is array then we have multiple parents, we need to remove all candidateElements
						candidateNewParent.append(candidateElement.first());
						if(candidateParent.length > 1) {
							candidateParent.each(function(index, element) {
									$(element).find(candidateElement).remove();
							});
						}

					} else {
						Vtiger_Helper_Js.showPnotify({text: data.error.message, type: 'error'});
					}
				});
			}
		},

		registerEvents: function () {
			this._super();
			this.candidatesSupport();
			this.candidatesDragAndDropSupport();
		}
	}
);
