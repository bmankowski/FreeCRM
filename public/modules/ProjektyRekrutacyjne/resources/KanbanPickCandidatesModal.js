'use strict';

(function () {
	const KanbanPickCandidatesModal = {
		$root: null,
		projectId: '',
		cvSkills: '',
		currentPage: 1,
		selectedIds: {},

		init: function ($root) {
			this.$root = $root;
			this.projectId = String(jQuery('#kanbanPickProjectId').val() || '');
			this.cvSkills = String(jQuery('#kanbanPickCvSkills').val() || '');
			this.currentPage = 1;
			this.selectedIds = {};
			if (!this.projectId || !this.cvSkills) {
				return;
			}
			this.registerResizer();
			this.registerEvents();
			this.loadPage(1);
		},

		getListBody: function () {
			return this.$root.find('.js-kanban-pick-list-body');
		},

		getFrame: function () {
			return this.$root.find('.js-kanban-pick-cv-frame');
		},

		loadPage: function (page) {
			const thisInstance = this;
			const progress = jQuery.progressIndicator({ position: 'html', blockInfo: { enabled: true } });
			AppConnector.request({
				module: 'ProjektyRekrutacyjne',
				action: 'KanbanPickCandidatesAjax',
				projectId: this.projectId,
				cv_skills: this.cvSkills,
				page: page
			}).done(function (data) {
				progress.progressIndicator({ mode: 'hide' });
				if (!data.success || !data.result || data.result.success !== true) {
					const msg = (data.result && data.result.message)
						? app.vtranslate(data.result.message, 'ProjektyRekrutacyjne')
						: app.vtranslate('PLL_NO_SUCH_RECORD', 'ProjektyRekrutacyjne');
					Vtiger_Helper_Js.showPnotify({ text: msg, type: 'error' });
					return;
				}
				const result = data.result;
				thisInstance.currentPage = result.pageNumber || page;
				thisInstance.getListBody().html(result.html || '');
				thisInstance.updatePager(result);
				thisInstance.restoreCheckboxState();
				thisInstance.activateFirstRow();
			}).fail(function (_jqXHR, _textStatus, errorThrown) {
				progress.progressIndicator({ mode: 'hide' });
				Vtiger_Helper_Js.showPnotify({
					text: errorThrown || app.vtranslate('PLL_ACCEPTANCE_FAILED', 'ProjektyRekrutacyjne'),
					type: 'error'
				});
			});
		},

		updatePager: function (result) {
			const total = result.totalCount || 0;
			const page = result.pageNumber || 1;
			const pageCount = result.pageCount || 1;
			const entries = result.entriesCount || 0;
			const countLabel = app.vtranslate('LBL_KANBAN_PICK_CANDIDATES_COUNT', 'ProjektyRekrutacyjne')
				.replace('%s', String(total));
			this.$root.find('.js-kanban-pick-count').text(countLabel);

			const prevDisabled = page <= 1 ? ' disabled' : '';
			const nextDisabled = page >= pageCount ? ' disabled' : '';
			const pagerHtml = '<button type="button" class="btn btn-default btn-sm js-kanban-pick-prev"' + prevDisabled + '>'
				+ '<span class="glyphicon glyphicon-chevron-left"></span></button>'
				+ '<span class="kanban-pick-candidates__page-info">' + page + ' / ' + pageCount + '</span>'
				+ '<button type="button" class="btn btn-default btn-sm js-kanban-pick-next"' + nextDisabled + '>'
				+ '<span class="glyphicon glyphicon-chevron-right"></span></button>';
			this.$root.find('.js-kanban-pick-pager').html(pagerHtml);

			if (entries === 0 && total === 0) {
				this.getFrame().attr('src', 'about:blank');
			}
		},

		restoreCheckboxState: function () {
			const selected = this.selectedIds;
			this.$root.find('.js-kanban-pick-row').each(function () {
				const id = String(jQuery(this).data('id') || '');
				if (id && selected[id]) {
					jQuery(this).find('.js-kanban-pick-checkbox').prop('checked', true);
				}
			});
		},

		activateFirstRow: function () {
			const $rows = this.$root.find('.js-kanban-pick-row');
			if (!$rows.length) {
				return;
			}
			const $active = $rows.filter('.active');
			if ($active.length) {
				this.loadPreview(String($active.data('id') || ''), $active);
				return;
			}
			const $first = $rows.first();
			$first.addClass('active');
			this.loadPreview(String($first.data('id') || ''), $first);
		},

		loadPreview: function (candidateId, $row) {
			if (!candidateId) {
				return;
			}
			if (typeof this.cancelResizeDrag === 'function') {
				this.cancelResizeDrag();
			}
			if ($row && $row.length) {
				this.$root.find('.js-kanban-pick-row').removeClass('active');
				$row.addClass('active');
			}
			const frame = this.getFrame();
			const url = 'index.php?module=Candidates&view=CvTextPreview&record=' + encodeURIComponent(candidateId)
				+ '&highlight=' + encodeURIComponent(this.cvSkills);
			frame.attr('src', url);
		},

		registerResizer: function () {
			const thisInstance = this;
			const $container = this.$root.find('.kanban-pick-candidates__split');
			const $list = this.$root.find('.js-kanban-pick-list');
			const $detail = this.$root.find('.js-kanban-pick-preview');
			const $divider = this.$root.find('.js-kanban-pick-resizer');
			const storageKey = 'FreeCRM.KanbanPickCandidates.listWidthPx';
			let resizing = false;
			let activePointerId = null;

			const applyListWidth = function (px) {
				const totalW = $container.width() || thisInstance.$root.width();
				if (!totalW) {
					return;
				}
				const dividerW = $divider.outerWidth() || 10;
				const minList = 280;
				const minDetail = 320;
				const maxList = Math.max(minList, totalW - dividerW - minDetail);
				const w = Math.max(minList, Math.min(maxList, px));
				$list.css({ flex: '0 0 ' + w + 'px', width: w + 'px', maxWidth: w + 'px' });
				$detail.css({ flex: '1 1 auto', width: 'auto', minWidth: minDetail + 'px' });
			};

			const finishResize = function (pointerId) {
				if (!resizing) {
					return;
				}
				resizing = false;
				activePointerId = null;
				thisInstance.$root.removeClass('is-resizing');
				jQuery('body').removeClass('unselectable').css('cursor', '');
				jQuery(document).off('.kanbanPickResizeDrag');
				if (typeof pointerId === 'number') {
					try {
						$divider[0].releasePointerCapture(pointerId);
					} catch (_e) {
					}
				}
				try {
					window.localStorage.setItem(storageKey, String($list.outerWidth() || 0));
				} catch (_e2) {
				}
			};

			this.cancelResizeDrag = function () {
				finishResize(activePointerId);
			};

			try {
				const stored = parseInt(window.localStorage.getItem(storageKey) || '', 10);
				if (!isNaN(stored) && stored > 0) {
					applyListWidth(stored);
				}
			} catch (_e) {
			}

			$divider.off('pointerdown.kanbanPickResize').on('pointerdown.kanbanPickResize', function (event) {
				if (event.button !== 0 || resizing) {
					return;
				}
				event.preventDefault();
				event.stopPropagation();
				const dividerEl = this;
				const startX = event.pageX;
				const startW = $list.outerWidth() || 0;
				activePointerId = event.pointerId;

				resizing = true;
				thisInstance.$root.addClass('is-resizing');
				jQuery('body').addClass('unselectable').css('cursor', 'col-resize');
				try {
					dividerEl.setPointerCapture(event.pointerId);
				} catch (_e) {
				}

				jQuery(document).off('.kanbanPickResizeDrag');
				jQuery(document).on('pointermove.kanbanPickResizeDrag', function (moveEvent) {
					if (!resizing) {
						return;
					}
					moveEvent.preventDefault();
					applyListWidth(startW + (moveEvent.pageX - startX));
				});
				jQuery(document).on('pointerup.kanbanPickResizeDrag pointercancel.kanbanPickResizeDrag', function (upEvent) {
					finishResize(upEvent.pointerId);
				});
			});
		},

		registerEvents: function () {
			const thisInstance = this;
			const $root = this.$root;

			$root.off('click.kanbanPickRow', '.js-kanban-pick-row');
			$root.on('click.kanbanPickRow', '.js-kanban-pick-row', function (event) {
				if (jQuery(event.target).closest('.js-kanban-pick-checkbox').length) {
					return;
				}
				const $row = jQuery(this);
				thisInstance.loadPreview(String($row.data('id') || ''), $row);
			});

			$root.off('change.kanbanPickCheckbox', '.js-kanban-pick-checkbox');
			$root.on('change.kanbanPickCheckbox', '.js-kanban-pick-checkbox', function (event) {
				event.stopPropagation();
				const $row = jQuery(this).closest('.js-kanban-pick-row');
				const id = String($row.data('id') || '');
				if (!id) {
					return;
				}
				if (jQuery(this).is(':checked')) {
					thisInstance.selectedIds[id] = true;
				} else {
					delete thisInstance.selectedIds[id];
				}
			});

			$root.off('change.kanbanPickSelectPage', '.js-kanban-pick-select-page');
			$root.on('change.kanbanPickSelectPage', '.js-kanban-pick-select-page', function () {
				const checked = jQuery(this).is(':checked');
				$root.find('.js-kanban-pick-checkbox').each(function () {
					jQuery(this).prop('checked', checked).trigger('change');
				});
			});

			$root.off('click.kanbanPickPager', '.js-kanban-pick-prev, .js-kanban-pick-next');
			$root.on('click.kanbanPickPager', '.js-kanban-pick-prev, .js-kanban-pick-next', function (event) {
				event.preventDefault();
				if (jQuery(this).is(':disabled')) {
					return;
				}
				const delta = jQuery(this).hasClass('js-kanban-pick-prev') ? -1 : 1;
				thisInstance.loadPage(thisInstance.currentPage + delta);
			});

			jQuery(document).off('click.kanbanPickAdd', '.js-kanban-pick-candidates-add');
			jQuery(document).on('click.kanbanPickAdd', '.js-kanban-pick-candidates-add', function (event) {
				event.preventDefault();
				const ids = Object.keys(thisInstance.selectedIds);
				if (!ids.length) {
					Vtiger_Helper_Js.showPnotify({
						text: app.vtranslate('LBL_SELECT_RECORD', 'Vtiger'),
						type: 'error'
					});
					return;
				}
				const detailInstance = Vtiger_Detail_Js.getInstance();
				if (!detailInstance || typeof detailInstance.submitManualCandidates !== 'function') {
					return;
				}
				detailInstance.submitManualCandidates(thisInstance.projectId, ids).done(function (data) {
					const result = (data && data.result) ? data.result : {};
					const ok = data && data.success === true
						&& (result.success === true || (result.added && result.added.length));
					if (ok || (result.skipped && result.skipped.length)) {
						app.hideModalWindow();
					}
				});
			});
		}
	};

	jQuery(function () {
		const $root = jQuery('.kanban-pick-candidates');
		if ($root.length) {
			KanbanPickCandidatesModal.init($root);
		}
	});
}());
