/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A., IT CONNECT Sp. z o. o.
 *************************************************************************************/
'use strict';

Vtiger_RelatedList_Js(
	'ProjektyRekrutacyjne_RelatedList_Js',
	{
		updatePreview: function (url) {
			let frame = this.content.find('.listPreviewframe');
			this.frameProgress = $.progressIndicator({
				position: 'html',
				message: app.vtranslate('JS_FRAME_IN_PROGRESS'),
				blockInfo: {
					enabled: false
				}
			});
			let defaultView = '';
			if (app.getMainParams('defaultDetailViewName')) {
				defaultView =
					defaultView + '&mode=showDetailViewByMode&requestMode=' + app.getMainParams('defaultDetailViewName'); // full, summary
			}
			frame.attr('src', url.replace('view=Detail', 'view=DetailPreview') + defaultView);
			// BMN changes
			let idCandidate = this.content.find('#candidateId');
			if (idCandidate.length) {
				const queryString = url.split('?')[1];
				const params = new URLSearchParams(queryString);
				const recordValue = params.get('record');
				idCandidate.val(recordValue);
			}
			// Create a new URL object
			let urlObj = new URL(window.location.href);
			// Get the search parameters from the URL
			let params = new URLSearchParams(urlObj.search);
			// Change the 'relatedRecord' parameter value
			params.set('relatedRecord', idCandidate.val());
			// Update the URL with the new query string
			urlObj.search = params.toString();
			// Get the updated URL as a string
			let updatedUrl = urlObj.toString();
			history.replaceState(null, '', updatedUrl);
		},
		registerPreviewEvent: function () {
			let thisInstance = this;
			let contentHeight = this.content.find('.js-detail-preview,.js-list-preview');
			contentHeight.height(app.getScreenHeight() - (this.content.offset().top + $('.js-footer').height()));
			this.content.find('.listPreviewframe').on('load', function () {
				if (thisInstance.frameProgress) {
					thisInstance.frameProgress.progressIndicator({mode: 'hide'});
				}
				contentHeight.height($(this).contents().find('.bodyContents').height() + 2);
			});
			//BMN changes
			let listViewEntries = this.content.find('.listViewEntriesTable .listViewEntries');
			const urlParams = new URLSearchParams(window.location.search);
			let relatedRecordId = Number(urlParams.get('relatedRecord'));
			if (relatedRecordId) {
				for (let i = 0; i < listViewEntries.length; i++) {
					let recordId = $(listViewEntries[i]).data('id');
					if (recordId === relatedRecordId) {
						$(listViewEntries[i]).trigger('click');
						$(document).find('.bigLoading').parent().remove();
					}
				}
			} else {
				listViewEntries.first().trigger('click');
				$(document).find('.bigLoading').parent().remove();
			}
		},
		acceptCandidateManually: function () {

			const container = $('.contentsDiv');
			container.on('click', '.acceptCandidateManually', function () {
				AppConnector.request({
					module: app.getModuleName(),
					parent: app.getParentModuleName(),
					action: 'AcceptCandidateManuallyAjax',
					candidateId: container.find('#candidateId').val(),
					projectId: container.find('#projectId').val()
				}).done(function (data) {
					if (data.result.success) {
						Vtiger_Detail_Js.reloadRelatedList();
						app.showNotify(app.vtranslate(data.result.message));
						setTimeout(function (){
							container.find('.listViewEntriesTable .listViewEntries').first().trigger('click'); // BMN changes
							this.updatePreview();
							$(document).find('.bigLoading').parent().remove();
						}, 1000);

					}
				}).fail(function (_error) {
					progressIndicator.progressIndicator({mode: 'hide'});
					app.showNotify(app.vtranslate(data.result.message));
				});
			});
		},
		rejectCandidateManually: function () {
			//Log function entrance
			const container = $('.contentsDiv');
			container.on('click', '.rejectCandidateManually', function () {
				const progressIndicator = $.progressIndicator();
				const candidateId = container.find('#candidateId').val();
				const projectId = container.find('#projectId').val();
				// alert("Rejection " + candidateId + " " + projectId);
				AppConnector.request({
					module: app.getModuleName(),
					action: 'RejectCandidateManuallyAjax',
					candidateId: candidateId,
					projectId: projectId
				}).done(function (data) {
					progressIndicator.progressIndicator({mode: 'hide'});

					if (data.result.success) {
						Vtiger_Detail_Js.reloadRelatedList();
						app.showNotify(app.vtranslate(data.result.message));
						setTimeout(function (){
							container.find('.listViewEntriesTable .listViewEntries').first().trigger('click'); // BMN changes
							this.updatePreview();
							$(document).find('.bigLoading').parent().remove();
						}, 1000);
					}
				}).fail(function (_error) {
					progressIndicator.progressIndicator({mode: 'hide'});
					app.showNotify(app.vtranslate(data.result.message));
				});
			});
		},
		handleKeyEvents: function () {
			// $(document)
			// Usunięcie poprzedniego nasłuchiwania przed dodaniem nowego
			$(document).off('keyup'); // Usuwa wszelkie wcześniejsze handlery dla 'keyup'
			// Dodanie nowego nasłuchiwania
			$(document).on('keyup', function (event) {
				if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
					let urlParams = new URLSearchParams(window.location.search);
					let relatedRecordId = Number(urlParams.get('relatedRecord'));
					let listViewEntries = $(document).find('.listViewEntriesTable .listViewEntries');
					if (relatedRecordId) {

						for (let i = 0; i < listViewEntries.length; i++) {
							let relatedRecordId = Number(urlParams.get('relatedRecord'));
							let recordId = $(listViewEntries[i]).data('id');
							if (recordId === relatedRecordId) {
								if(event.key === 'ArrowUp' && i >= 1) {
									$(listViewEntries[i-1]).trigger('click');
									$(document).find('.bigLoading').parent().remove();
								}
								if(event.key === 'ArrowDown' && i < listViewEntries.length - 1) {
									$(listViewEntries[i+1]).trigger('click');
									$(document).find('.bigLoading').parent().remove();
								}
							}
						}
					} else {
						listViewEntries.first().trigger('click');
					}

				}
			});
		},
		/**
		 * Register related events
		 */
		registerRelatedEvents: function () {
			this._super();

			this.acceptCandidateManually();
			this.rejectCandidateManually();
			this.handleKeyEvents();
		}
	}
);
// https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&relatedModule=Kandydaci&view=Detail&record=1376448&mode=showRelatedList&relationId=756&tab_label=Kandydaci
// https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&relatedModule=Kandydaci&view=Detail&record=1376448&mode=showRelatedList&relationId=756&tab_label=Kandydaci&entityState=Active&advancedConditions=null&search_params=[[["recruitment_status_rel%22e%22PPL_REJECTED_AFTER_CV"]]]&totalCount=0
// 	https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&view=List&viewname=133&page=1&orderby=%7B%22createdtime%22%3A%22DESC%22%7D&entityState=Active&advancedConditions=null&search_params=%5B%5B%5B%22etap_sprzedazy%22%2C%22e%22%2C%22Aktywna%22%5D%5D%5D&totalCount=0
