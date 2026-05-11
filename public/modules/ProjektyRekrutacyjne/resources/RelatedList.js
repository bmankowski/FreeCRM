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

		/**
		 * Odcinki offset/scroll wymuszają layout — odkładamy za pełnym load dokumentu i po dwóch rAF,
		 * żeby ograniczyć ostrzeżenia przeglądarki przy wczesnym wywoływaniu (FOUC/layout).
		 */
		_projListPreviewGeomTimer: null,
		_previewBusyClearTimer: null,
		clearPreviewProgressGuard: function () {
			if (this._previewBusyClearTimer) {
				clearTimeout(this._previewBusyClearTimer);
				this._previewBusyClearTimer = null;
			}
		},
		/**
		 * $.progressIndicator({ … }) oprócz blokowania nakłada pusty kontener fixed w body —
		 * sama metoda hide() usuwa .imageHolder, ale ten wrapper zostaje (z-index ~100000) i blokuje UI.
		 */
		disposeFloatingProgress: function ($jq) {
			if (!$jq || !$jq.length) {
				return;
			}
			try {
				$jq.progressIndicator({mode: 'hide'});
			} catch (_e) {
				/* ignore */
			}
			try {
				$jq.remove();
			} catch (_e2) {
				/* ignore */
			}
		},
		stripOrphanFloatingLoaders: function () {
			try {
				$('body > div').filter(function () {
					const $ch = $(this);
					if (!$ch.find('.sk-cube-grid,.imageHolder.bigLoading,.imageHolder.smallLoading').length) {
						return false;
					}
					const pos = ($ch.css('position') || '').toLowerCase();
					const zi = parseInt($ch.css('z-index'), 10);
					return pos === 'fixed' && !isNaN(zi) && zi >= 99990;
				}).remove();
			} catch (_e) {
				/* ignore */
			}
		},
		hideFrameProgressSafely: function () {
			this.clearPreviewProgressGuard();
			if (this.frameProgress && this.frameProgress.length) {
				this.disposeFloatingProgress(this.frameProgress);
				this.frameProgress = null;
			}
			this.stripOrphanFloatingLoaders();
		},
		resizeListPreviewLayoutDeferred: function () {
			const runGeom = () => {
				try {
					this.resizeListPreviewLayout();
				} catch (_e) {
					/* ignore */
				}
			};
			// Nie czekamy na window "load" — jeśli zdarzenie już minęło, kod nigdy by się nie uruchomił (wiszący UI).
			if (typeof window.requestAnimationFrame === 'function') {
				window.requestAnimationFrame(() => window.requestAnimationFrame(runGeom));
			} else {
				setTimeout(runGeom, 0);
			}
		},
		debounceResizeListPreviewLayout: function () {
			clearTimeout(this._projListPreviewGeomTimer);
			this._projListPreviewGeomTimer = setTimeout(() => {
				this.resizeListPreviewLayoutDeferred();
			}, 120);
		},

		updatePreview: function (url) {
			const thisInstance = this;
			const content = this.getContentContainer();
			let frame = content.find('.listPreviewframe');
			this.hideFrameProgressSafely();
			this.frameProgress = $.progressIndicator({
				position: 'html',
				message: app.vtranslate('JS_FRAME_IN_PROGRESS'),
				blockInfo: {
					enabled: false
				}
			});
			// Render summary-only preview (iframe-friendly, no full app chrome).
			let previewUrl = url.replace('view=Detail', 'view=Preview');
			// Bez tego przeglądarka często NIE wywoła "load", gdy adres jest jak poprzednio — wtedy spinner zostaje na zawsze.
			previewUrl += (previewUrl.indexOf('?') >= 0 ? '&' : '?') + '_iframe_pv=' + Date.now();
			frame.attr('src', previewUrl);
			this.clearPreviewProgressGuard();
			this._previewBusyClearTimer = setTimeout(function () {
				thisInstance.hideFrameProgressSafely();
			}, 20000);
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
			params.set('relatedRecord', idCandidate.length ? idCandidate.val() : '');
			// Update the URL with the new query string
			urlObj.search = params.toString();
			// Get the updated URL as a string
			let updatedUrl = urlObj.toString();
			history.replaceState(null, '', updatedUrl);
		},
		registerPreviewEvent: function () {
			let thisInstance = this;
			const content = this.getContentContainer();
			content.find('.listPreviewframe')
				.off('load.projListPreviewIframe error.projListPreviewIframe')
				.on('load.projListPreviewIframe error.projListPreviewIframe', function () {
					thisInstance.hideFrameProgressSafely();
					thisInstance.resizeListPreviewLayoutDeferred();
					setTimeout(function () {
						thisInstance.resizeListPreviewLayoutDeferred();
					}, 320);
				});
			$(window).off('resize.projListPreviewLayout').on('resize.projListPreviewLayout', () => thisInstance.debounceResizeListPreviewLayout());
			this.resizeListPreviewLayoutDeferred();
			//BMN changes
			let listViewEntries = content.find('.listViewEntriesTable .listViewEntries');
			const urlParams = new URLSearchParams(window.location.search);
			let relatedRecordId = Number(urlParams.get('relatedRecord'));
			if (relatedRecordId) {
				for (let i = 0; i < listViewEntries.length; i++) {
					let recordId = $(listViewEntries[i]).data('id');
					if (recordId === relatedRecordId) {
						$(listViewEntries[i]).trigger('click');
						thisInstance.stripOrphanFloatingLoaders();
					}
				}
			} else {
				listViewEntries.first().trigger('click');
				thisInstance.stripOrphanFloatingLoaders();
			}
		},
		reloadListPreviewAfterStatusChange: function () {
			const thisInstance = this;
			const detailInstance = Vtiger_Detail_Js.getInstance();
			const selectedTab = detailInstance && typeof detailInstance.getSelectedTab === 'function' ? detailInstance.getSelectedTab() : $();
			const params = {page: 1};
			if (selectedTab.length) {
				params.tab_label = selectedTab.data('label-key');
			}
			const refreshPreview = function () {
				const fresh = $('.RelatedList.relatedContainer').has('.listPreviewframe').first();
				const firstRow = fresh.find('.listViewEntriesTable .listViewEntries').first();
				const recordUrl = firstRow.data('recordurl');
				if (recordUrl && typeof thisInstance.updatePreview === 'function') {
					thisInstance.updatePreview(recordUrl);
				} else {
					fresh.find('#candidateId').val('');
					fresh.find('.listPreviewframe').attr('src', 'about:blank');
				}
				thisInstance.stripOrphanFloatingLoaders();
			};
			const loadPromise = detailInstance.loadRelatedList(params);
			if (loadPromise && typeof loadPromise.then === 'function') {
				loadPromise.then(refreshPreview, refreshPreview);
			} else {
				setTimeout(refreshPreview, 1000);
			}
		},
		closeRejectionReasonMenu: function (root) {
			const scope = root && root.length ? root : $('.RelatedList.relatedContainer');
			scope.find('.c-candidate-thumb-actions')
				.removeClass('is-rejection-reasons-open')
				.find('.rejectCandidateManually')
				.attr('aria-expanded', 'false');
		},
		acceptCandidateManually: function () {
			const thisInstance = this;
			$(document).off('click.projAcceptCandidate', '.RelatedList.relatedContainer .acceptCandidateManually').on(
				'click.projAcceptCandidate',
				'.RelatedList.relatedContainer .acceptCandidateManually',
				function (e) {
					e.preventDefault();
					const root = $(this).closest('.RelatedList.relatedContainer');
					thisInstance.closeRejectionReasonMenu(root);
					const candidateId = root.find('#candidateId').val();
					const projectId = root.find('#projectId').val();
					if (!candidateId || !projectId) {
						Vtiger_Helper_Js.showPnotify({
							text: app.vtranslate('LBL_SELECT_RECORD', 'Vtiger'),
							type: 'error'
						});
						return false;
					}
					const progressIndicator = $.progressIndicator({position: 'html', message: '', blockInfo: {enabled: false}});
					console.info('[ProjektyRekrutacyjne] Akcja: akceptacja kandydata → wysyłanie', {
						candidateId: candidateId,
						projectId: projectId,
						action: 'AcceptCandidateManuallyAjax'
					});
					AppConnector.request({
						module: app.getModuleName(),
						parent: app.getParentModuleName(),
						action: 'AcceptCandidateManuallyAjax',
						candidateId: candidateId,
						projectId: projectId
					}).done(function (data) {
						thisInstance.disposeFloatingProgress(progressIndicator);
						if (data && data.result && data.result.success) {
							console.info('[ProjektyRekrutacyjne] Akcja: akceptacja kandydata → zakończona OK', {
								candidateId: candidateId,
								projectId: projectId,
								message: data.result.message
							});
							thisInstance.reloadListPreviewAfterStatusChange();
							Vtiger_Helper_Js.showPnotify({
								text: app.vtranslate(data.result.message),
								type: 'success',
								animation: 'show'
							});
						} else {
							console.warn('[ProjektyRekrutacyjne] Akcja: akceptacja kandydata → odpowiedź bez success', {
								candidateId: candidateId,
								projectId: projectId,
								result: data && data.result ? data.result : data
							});
						}
					}).fail(function (error) {
						thisInstance.disposeFloatingProgress(progressIndicator);
						const msg =
							error && error.result && error.result.message
								? error.result.message
								: error && error.error && error.error.message
									? error.error.message
									: 'PLL_ACCEPTANCE_FAILED';
						console.error('[ProjektyRekrutacyjne] Akcja: akceptacja kandydata → błąd', {
							candidateId: candidateId,
							projectId: projectId,
							message: msg,
							error: error
						});
						Vtiger_Helper_Js.showPnotify({
							text: app.vtranslate(typeof msg === 'string' ? msg : 'PLL_ACCEPTANCE_FAILED'),
							type: 'error'
						});
					});
					return false;
				}
			);
		},
		sendRejectCandidateManually: function (root, rejectionReason) {
			const thisInstance = this;
			const candidateId = root.find('#candidateId').val();
			const projectId = root.find('#projectId').val();
			if (!candidateId || !projectId) {
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate('LBL_SELECT_RECORD', 'Vtiger'),
					type: 'error'
				});
				return false;
			}
			const progressIndicator = $.progressIndicator({position: 'html', message: '', blockInfo: {enabled: false}});
			console.info('[ProjektyRekrutacyjne] Akcja: odrzucenie kandydata → wysyłanie', {
				candidateId: candidateId,
				projectId: projectId,
				rejectionReason: rejectionReason,
				action: 'RejectCandidateManuallyAjax'
			});
			AppConnector.request({
				module: app.getModuleName(),
				action: 'RejectCandidateManuallyAjax',
				candidateId: candidateId,
				projectId: projectId,
				rejectionReason: rejectionReason
			}).done(function (data) {
				thisInstance.disposeFloatingProgress(progressIndicator);
				if (data && data.result && data.result.success) {
					console.info('[ProjektyRekrutacyjne] Akcja: odrzucenie kandydata → zakończona OK', {
						candidateId: candidateId,
						projectId: projectId,
						rejectionReason: rejectionReason,
						message: data.result.message
					});
					thisInstance.reloadListPreviewAfterStatusChange();
					Vtiger_Helper_Js.showPnotify({
						text: app.vtranslate(data.result.message),
						type: 'success',
						animation: 'show'
					});
				} else {
					console.warn('[ProjektyRekrutacyjne] Akcja: odrzucenie kandydata → odpowiedź bez success', {
						candidateId: candidateId,
						projectId: projectId,
						rejectionReason: rejectionReason,
						result: data && data.result ? data.result : data
					});
				}
			}).fail(function (error) {
				thisInstance.disposeFloatingProgress(progressIndicator);
				const msg =
					error && error.result && error.result.message
						? error.result.message
						: error && error.error && error.error.message
							? error.error.message
							: 'PLL_REJECT_FAILED';
				console.error('[ProjektyRekrutacyjne] Akcja: odrzucenie kandydata → błąd', {
					candidateId: candidateId,
					projectId: projectId,
					rejectionReason: rejectionReason,
					message: msg,
					error: error
				});
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate(typeof msg === 'string' ? msg : 'PLL_REJECT_FAILED'),
					type: 'error'
				});
			});
			return true;
		},
		rejectCandidateManually: function () {
			const thisInstance = this;
			$(document).off('click.projRejectCandidate', '.RelatedList.relatedContainer .rejectCandidateManually').on(
				'click.projRejectCandidate',
				'.RelatedList.relatedContainer .rejectCandidateManually',
				function (e) {
					e.preventDefault();
					e.stopImmediatePropagation();
					const root = $(this).closest('.RelatedList.relatedContainer');
					const candidateId = root.find('#candidateId').val();
					const projectId = root.find('#projectId').val();
					if (!candidateId || !projectId) {
						Vtiger_Helper_Js.showPnotify({
							text: app.vtranslate('LBL_SELECT_RECORD', 'Vtiger'),
							type: 'error'
						});
						return false;
					}
					const dock = root.find('.c-candidate-thumb-actions');
					const shouldOpen = !dock.hasClass('is-rejection-reasons-open');
					thisInstance.closeRejectionReasonMenu(root);
					dock.toggleClass('is-rejection-reasons-open', shouldOpen);
					$(this).attr('aria-expanded', shouldOpen ? 'true' : 'false');
					if (shouldOpen) {
						dock.find('.rejectCandidateReason').first().trigger('focus');
					}
					return false;
				}
			);
			$(document).off('click.projRejectCandidateReason', '.RelatedList.relatedContainer .rejectCandidateReason').on(
				'click.projRejectCandidateReason',
				'.RelatedList.relatedContainer .rejectCandidateReason',
				function (e) {
					e.preventDefault();
					e.stopImmediatePropagation();
					const root = $(this).closest('.RelatedList.relatedContainer');
					const rejectionReason = $(this).data('rejectionReason');
					thisInstance.closeRejectionReasonMenu(root);
					thisInstance.sendRejectCandidateManually(root, rejectionReason);
					return false;
				}
			);
			$(document).off('keyup.projRejectCandidateMenu').on('keyup.projRejectCandidateMenu', function (e) {
				if (e.key === 'Escape') {
					thisInstance.closeRejectionReasonMenu();
				}
			});
			$(document).off('click.projRejectCandidateMenuClose').on('click.projRejectCandidateMenuClose', function (e) {
				if (!$(e.target).closest('.RelatedList.relatedContainer .c-candidate-thumb-actions').length) {
					thisInstance.closeRejectionReasonMenu();
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
		}
	}
);
// https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&relatedModule=Kandydaci&view=Detail&record=1376448&mode=showRelatedList&relationId=756&tab_label=Kandydaci
// https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&relatedModule=Kandydaci&view=Detail&record=1376448&mode=showRelatedList&relationId=756&tab_label=Kandydaci&entityState=Active&advancedConditions=null&search_params=[[["recruitment_status_rel%22e%22PPL_REJECTED_AFTER_CV"]]]&totalCount=0
// 	https://192.168.2.230/index.php?module=ProjektyRekrutacyjne&view=List&viewname=133&page=1&orderby=%7B%22createdtime%22%3A%22DESC%22%7D&entityState=Active&advancedConditions=null&search_params=%5B%5B%5B%22etap_sprzedazy%22%2C%22e%22%2C%22Aktywna%22%5D%5D%5D&totalCount=0
