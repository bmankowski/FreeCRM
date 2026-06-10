{strip}
<!-- layouts/basic/modules/Base/ReportIssueModal.tpl -->
<div class="modal fade reportIssueModal" id="reportIssueModal" tabindex="-1" role="dialog" aria-labelledby="reportIssueModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content validationEngineContainer">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="reportIssueModalLabel">{"LBL_REPORT_ISSUE"|t:"ReportIssue"}</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="reportIssueTitle"><span class="redColor">*</span> {"LBL_REPORT_ISSUE_TITLE"|t:"ReportIssue"}</label>
					<input type="text" id="reportIssueTitle" class="form-control" maxlength="255"
						data-validation-engine="validate[required]" />
				</div>
				<div class="form-group">
					<label for="reportIssueDescription"><span class="redColor">*</span> {"LBL_REPORT_ISSUE_DESCRIPTION"|t:"ReportIssue"}</label>
					<textarea id="reportIssueDescription" class="form-control" rows="4"
						data-validation-engine="validate[required]"></textarea>
				</div>
				<div class="form-group">
					<label for="reportIssuePageUrl">{"LBL_REPORT_ISSUE_PAGE_URL"|t:"ReportIssue"}</label>
					<input type="text" id="reportIssuePageUrl" class="form-control" readonly="readonly" tabindex="-1" />
				</div>
				<div class="form-group">
					<label>{"LBL_REPORT_ISSUE_SCREENSHOT"|t:"ReportIssue"}</label>
					<div class="reportIssueScreenshotPreview text-center">
						<img id="reportIssueScreenshotImg" class="img-responsive img-thumbnail hide" alt="" />
						<p id="reportIssueScreenshotPlaceholder" class="text-muted">{"LBL_REPORT_ISSUE_SCREENSHOT_LOADING"|t:"ReportIssue"}</p>
					</div>
					<button type="button" class="btn btn-default btn-sm marginTop5 js-report-issue-refresh-screenshot">
						{"LBL_REPORT_ISSUE_REFRESH_SCREENSHOT"|t:"ReportIssue"}
					</button>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-warning" data-dismiss="modal">{"LBL_CANCEL"|t:"Base"}</button>
				<button type="button" class="btn btn-success js-report-issue-submit">
					{"LBL_REPORT_ISSUE_SUBMIT"|t:"ReportIssue"}
				</button>
			</div>
		</div>
	</div>
</div>
<!--/layouts/basic/modules/Base/ReportIssueModal.tpl -->
{/strip}
