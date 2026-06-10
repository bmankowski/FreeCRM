/*+**********************************************************************************
 * Report Issue widget — HelpDesk ticket + optional GitHub issue
 *************************************************************************************/
'use strict';

jQuery(document).ready(function () {
	var ReportIssue = {
		modal: null,
		formContainer: null,
		screenshotBlob: null,
		reportPageUrl: null,
		capturing: false,

		init: function () {
			var self = this;
			this.modal = jQuery('#reportIssueModal');
			if (!this.modal.length) {
				return;
			}
			this.formContainer = this.modal.find('.validationEngineContainer');
			this.formContainer.validationEngine(app.validationEngineOptions);

			jQuery(document).on('click', '.js-report-issue-btn', function (e) {
				e.preventDefault();
				self.openModal();
			});
			this.modal.on('click', '.js-report-issue-refresh-screenshot', function () {
				self.captureScreenshot({ reopenModal: true });
			});
			this.modal.on('click', '.js-report-issue-submit', function () {
				self.submit();
			});
		},

		focusTitleInput: function () {
			var input = this.modal.find('#reportIssueTitle');
			if (input.length) {
				window.setTimeout(function () {
					input.trigger('focus');
				}, 0);
			}
		},

		showModalAndFocusTitle: function () {
			var self = this;
			this.modal.one('shown.bs.modal', function () {
				self.focusTitleInput();
			});
			this.modal.modal('show');
		},

		openModal: function () {
			this.reportPageUrl = window.location.href;
			this.modal.find('#reportIssueTitle').val('');
			this.modal.find('#reportIssueDescription').val('');
			this.modal.find('#reportIssuePageUrl').val(this.reportPageUrl);
			this.updateSubmitState();
			this.captureScreenshot({ reopenModal: false });
		},

		updateSubmitState: function () {
			this.modal.find('.js-report-issue-submit').prop('disabled', this.capturing);
		},

		captureScreenshot: function (options) {
			var self = this;
			var opts = jQuery.extend({ reopenModal: true }, options);
			if (typeof html2canvas === 'undefined') {
				this.showScreenshotError(app.vtranslate('LBL_REPORT_ISSUE_NO_HTML2CANVAS', 'ReportIssue'));
				this.showModalAndFocusTitle();
				return;
			}
			this.capturing = true;
			this.updateSubmitState();
			this.modal.find('#reportIssueScreenshotImg').addClass('hide');
			this.modal.find('#reportIssueScreenshotPlaceholder')
				.removeClass('hide')
				.text(app.vtranslate('LBL_REPORT_ISSUE_SCREENSHOT_LOADING', 'ReportIssue'));

			var progress;
			if (opts.reopenModal) {
				this.modal.modal('hide');
			} else {
				progress = jQuery.progressIndicator({ blockInfo: { enabled: true } });
			}

			window.setTimeout(function () {
				html2canvas(document.body, {
					logging: false,
					useCORS: true,
					ignoreElements: function (el) {
						return el.classList && (
							el.classList.contains('reportIssueModal') ||
							el.classList.contains('modal-backdrop')
						);
					}
				}).then(function (canvas) {
					canvas.toBlob(function (blob) {
						self.screenshotBlob = blob;
						self.capturing = false;
						if (progress) {
							progress.progressIndicator({ mode: 'hide' });
						}
						self.showModalAndFocusTitle();
						if (blob) {
							var url = URL.createObjectURL(blob);
							self.modal.find('#reportIssueScreenshotImg').attr('src', url).removeClass('hide');
							self.modal.find('#reportIssueScreenshotPlaceholder').addClass('hide');
						} else {
							self.showScreenshotError(app.vtranslate('LBL_REPORT_ISSUE_SCREENSHOT_FAILED', 'ReportIssue'));
						}
						self.updateSubmitState();
					}, 'image/png');
				}).catch(function () {
					self.screenshotBlob = null;
					self.capturing = false;
					if (progress) {
						progress.progressIndicator({ mode: 'hide' });
					}
					self.showModalAndFocusTitle();
					self.showScreenshotError(app.vtranslate('LBL_REPORT_ISSUE_SCREENSHOT_FAILED', 'ReportIssue'));
					self.updateSubmitState();
				});
			}, opts.reopenModal ? 150 : 50);
		},

		showScreenshotError: function (message) {
			this.modal.find('#reportIssueScreenshotImg').addClass('hide');
			this.modal.find('#reportIssueScreenshotPlaceholder').removeClass('hide').text(message);
		},

		buildContext: function () {
			var version = '';
			if (typeof _USERMETA !== 'undefined' && _USERMETA && _USERMETA.version) {
				version = _USERMETA.version;
			}
			return {
				pageUrl: this.reportPageUrl || window.location.href,
				module: app.getModuleName(),
				view: app.getViewName(),
				recordId: app.getRecordId() || '',
				userAgent: navigator.userAgent,
				screenSize: window.screen.width + 'x' + window.screen.height,
				crmVersion: version
			};
		},

		submit: function () {
			var self = this;
			if (!this.formContainer.validationEngine('validate')) {
				return;
			}

			var formData = new FormData();
			formData.append('module', 'HelpDesk');
			formData.append('action', 'ReportIssue');
			formData.append('title', this.modal.find('#reportIssueTitle').val());
			formData.append('description', this.modal.find('#reportIssueDescription').val());
			formData.append('context', JSON.stringify(this.buildContext()));
			if (this.screenshotBlob) {
				formData.append('screenshot', this.screenshotBlob, 'report-issue-screenshot.png');
			}
			if (typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
				formData.append(csrfMagicName, csrfMagicToken);
			}

			var submitBtn = this.modal.find('.js-report-issue-submit');
			submitBtn.prop('disabled', true);
			var progress = jQuery.progressIndicator({blockInfo: {enabled: true}});

			jQuery.ajax({
				url: 'index.php',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json'
			}).done(function (data) {
				progress.progressIndicator({mode: 'hide'});
				if (data && data.result && data.result.success) {
					self.modal.modal('hide');
					var message = app.vtranslate('LBL_REPORT_ISSUE_SUCCESS', 'ReportIssue')
						.replace('%s', data.result.ticket_no || '');
					if (data.result.github_url) {
						message += ' <a href="' + data.result.github_url + '" target="_blank" rel="noopener">GitHub</a>';
					} else if (data.result.github_error) {
						message += ' ' + app.vtranslate('LBL_REPORT_ISSUE_GITHUB_ERROR', 'ReportIssue');
					}
					Vtiger_Helper_Js.showPnotify({
						text: message,
						type: 'success',
						animation: 'show',
						hide: false,
						sticker: false
					});
				} else {
					Vtiger_Helper_Js.showPnotify({
						text: app.vtranslate('LBL_REPORT_ISSUE_SUBMIT_FAILED', 'ReportIssue'),
						type: 'error',
						animation: 'show'
					});
				}
			}).fail(function () {
				progress.progressIndicator({mode: 'hide'});
				Vtiger_Helper_Js.showPnotify({
					text: app.vtranslate('LBL_REPORT_ISSUE_SUBMIT_FAILED', 'ReportIssue'),
					type: 'error',
					animation: 'show'
				});
			}).always(function () {
				submitBtn.prop('disabled', false);
				self.updateSubmitState();
			});
		}
	};

	ReportIssue.init();
});
