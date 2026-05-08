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
		getContentContainer: function () {
			// Base controller uses relatedContentContainer; older custom code used this.content.
			return this.relatedContentContainer || this.content || jQuery('.contentsDiv');
		},
		resizeListPreviewLayout: function () {
			const content = this.getContentContainer();
			let root = content.find('.RelatedList.relatedContainer').first();
			if (!root.length) {
				root = content.closest('.RelatedList.relatedContainer');
			}
			if (!root.length) {
				root = $('.RelatedList.relatedContainer').has('.listPreviewframe').first();
			}
			const frame = root.find('.listPreviewframe');
			const detail = root.find('.c-detail-preview');
			const list = root.find('.c-list-preview');
			const resizer = root.find('.c-list-preview-resizer');
			if (!frame.length || !detail.length || !root.length) {
				return;
			}
			let iframeContentH = 200;
			try {
				const el = frame[0];
				if (el.contentDocument && el.contentDocument.body) {
					const b = el.contentDocument.body;
					const e = el.contentDocument.documentElement;
					// Do not use e.clientHeight — it tracks the iframe viewport, not document length.
					iframeContentH = Math.max(
						b.scrollHeight || 0,
						b.offsetHeight || 0,
						e.scrollHeight || 0,
						e.offsetHeight || 0
					);
					const wrap = el.contentDocument.querySelector('.c-iframe-preview');
					if (wrap) {
						iframeContentH = Math.max(
							iframeContentH,
							wrap.scrollHeight || 0,
							wrap.offsetHeight || 0
						);
					}
				}
			} catch (_e) {
				/* ignore */
			}
			iframeContentH = Math.min(Math.max(iframeContentH + 24, 200), 50000);
			const frameDom = frame[0];
			if (frameDom && frameDom.style) {
				frameDom.style.setProperty('height', iframeContentH + 'px', 'important');
				frameDom.style.setProperty('min-height', '0', 'important');
			} else {
				frame.css({height: iframeContentH + 'px', flex: '0 0 auto', minHeight: '0'});
			}
			detail.css({height: 'auto', maxHeight: 'none', overflow: 'visible'});

			let headerBottom = root.offset().top;
			if (root.find('.relatedHeader').length) {
				const rh = root.find('.relatedHeader');
				headerBottom = rh.offset().top + rh.outerHeight();
			}
			const footerH = $('.js-footer').length ? $('.js-footer').outerHeight() : 0;
			const viewportCap = Math.max(320, $(window).height() - headerBottom - footerH - 20);
			const detailNatural = Math.ceil(detail.outerHeight(true));
			const targetColH = Math.max(viewportCap, detailNatural);

			list.add(resizer).css({
				height: targetColH + 'px',
				minHeight: Math.min(viewportCap, targetColH) + 'px',
				maxHeight: 'none'
			});
			detail.css({minHeight: targetColH + 'px'});
		},

		updatePreview: function (url) {
			const content = this.getContentContainer();
			let frame = content.find('.listPreviewframe');
			this.frameProgress = $.progressIndicator({
				position: 'html',
				message: app.vtranslate('JS_FRAME_IN_PROGRESS'),
				blockInfo: {
					enabled: false
				}
			});
			// Render summary-only preview (iframe-friendly, no full app chrome).
			frame.attr('src', url.replace('view=Detail', 'view=Preview'));
			// BMN changes
			let idCandidate = content.find('#candidateId');
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
			const content = this.getContentContainer();
			content.find('.listPreviewframe').off('load.projListPreviewIframe').on('load.projListPreviewIframe', function () {
				if (thisInstance.frameProgress) {
					thisInstance.frameProgress.progressIndicator({mode: 'hide'});
				}
				// Align iframe height with inner document so summary is visible without iframe scrollbars.
				setTimeout(function () {
					thisInstance.resizeListPreviewLayout();
					if (typeof window.requestAnimationFrame === 'function') {
						window.requestAnimationFrame(function () {
							thisInstance.resizeListPreviewLayout();
						});
					}
				}, 0);
				setTimeout(function () {
					thisInstance.resizeListPreviewLayout();
				}, 250);
			});
			$(window).off('resize.projListPreviewLayout').on('resize.projListPreviewLayout', () => thisInstance.resizeListPreviewLayout());
			this.resizeListPreviewLayout();
			//BMN changes
			let listViewEntries = content.find('.listViewEntriesTable .listViewEntries');
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

			// Ensure ListPreview preview pane is wired for Kandydaci related list.
			const content = this.getContentContainer();
			if (content.find('input.relatedView').val() === 'ListPreview') {
				this.registerPreviewEvent();
				// Clicking name link should not navigate away in ListPreview.
				content.off('click.projektyPreviewLink', '.listViewEntries a')
					.on('click.projektyPreviewLink', '.listViewEntries a', (e) => {
						e.preventDefault();
						e.stopPropagation();
						const row = jQuery(e.currentTarget).closest('.listViewEntries');
						const recordUrl = row.data('recordurl');
						if (recordUrl) {
							this.updatePreview(recordUrl);
						}
					});
				// Ensure something is loaded on first render (if not already selected).
				const firstRow = content.find('.listViewEntriesTable .listViewEntries').first();
				if (firstRow.length) {
					const recordUrl = firstRow.data('recordurl');
					if (recordUrl) {
						this.updatePreview(recordUrl);
					}
				}
			}
			this.acceptCandidateManually();
			this.rejectCandidateManually();
			this.handleKeyEvents();
		}
	}
);
// https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&relatedModule=Kandydaci&view=Detail&record=1376448&mode=showRelatedList&relationId=756&tab_label=Kandydaci
// https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&relatedModule=Kandydaci&view=Detail&record=1376448&mode=showRelatedList&relationId=756&tab_label=Kandydaci&entityState=Active&advancedConditions=null&search_params=[[["recruitment_status_rel%22e%22PPL_REJECTED_AFTER_CV"]]]&totalCount=0
// 	https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&view=List&viewname=133&page=1&orderby=%7B%22createdtime%22%3A%22DESC%22%7D&entityState=Active&advancedConditions=null&search_params=%5B%5B%5B%22etap_sprzedazy%22%2C%22e%22%2C%22Aktywna%22%5D%5D%5D&totalCount=0
