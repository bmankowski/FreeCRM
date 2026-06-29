<?php

namespace App\Modules\HelpDesk\Services;

/**
 * Creates HelpDesk tickets from Report Issue widget submissions.
 */
class ReportIssueService
{
	private GithubIssueSyncService $githubSync;

	public function __construct(?GithubIssueSyncService $githubSync = null)
	{
		$this->githubSync = $githubSync ?? new GithubIssueSyncService();
	}

	/**
	 * @return array{record: \App\Modules\Base\Models\Record, github_url: ?string, github_error: bool}
	 */
	public function createFromReport(
		string $title,
		string $description,
		array $context,
		?array $screenshotFile,
		\App\Modules\Users\Models\Record $reporter
	): array {
		$record = \App\Modules\HelpDesk\Models\Record::getCleanInstance('HelpDesk');
		$assignedUserId = (int) (\App\Core\AppConfig::module('ReportIssue', 'default_assigned_user_id') ?: $reporter->getId());
		$priority = (string) (\App\Core\AppConfig::module('ReportIssue', 'default_priority') ?: 'Normal');
		$status = (string) (\App\Core\AppConfig::module('ReportIssue', 'default_status') ?: 'Open');

		$record->set('ticket_title', $title);
		$record->set('description', $this->buildTicketDescription($description, $context));
		$record->set('ticketstatus', $status);
		$record->set('ticketpriorities', $priority);
		$record->set('assigned_user_id', $assignedUserId);
		$record->set('update_log', $this->buildUpdateLog($reporter, $assignedUserId));

		$pageUrl = trim((string) ($context['pageUrl'] ?? ''));
		if ($pageUrl !== '') {
			$record->set('report_issue_url', $pageUrl);
		}

		$parentId = $this->resolveParentId($context);
		if ($parentId > 0) {
			$record->set('parent_id', $parentId);
		}

		$record->save();

		$screenshotFilename = '';
		if ($screenshotFile !== null && ($screenshotFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
			$screenshotFilename = $record->attachReportIssueScreenshot($screenshotFile);
		}

		$githubUrl = null;
		$githubError = false;
		$context['userDescription'] = $description;
		$issue = $this->githubSync->createForTicket($record, $context, $screenshotFilename, $reporter);
		if ($issue !== null) {
			$record->isNew = false;
			$record->set('github_issue_url', $issue->html_url ?? '');
			if (isset($issue->number)) {
				$record->set('github_issue_number', (int) $issue->number);
			}
			$record->save();
			$githubUrl = $issue->html_url ?? null;
		} elseif (\App\Modules\Settings\Github\Models\Client::getInstance()->isAuthorized()) {
			$githubError = true;
		}

		return [
			'record' => $record,
			'github_url' => $githubUrl,
			'github_error' => $githubError,
		];
	}

	private function resolveParentId(array $context): int
	{
		$defaultParentId = (int) (\App\Core\AppConfig::module('ReportIssue', 'default_parent_id') ?: 0);
		if ($defaultParentId > 0) {
			return $defaultParentId;
		}

		$relatedRecordId = (int) ($context['recordId'] ?? 0);
		$relatedModule = (string) ($context['module'] ?? '');
		if ($relatedRecordId > 0 && $relatedModule !== '' && !in_array($relatedModule, ['HelpDesk', 'Users', 'Home'], true)) {
			return $relatedRecordId;
		}

		return 0;
	}

	private function buildTicketDescription(string $description, array $context): string
	{
		$parts = [trim($description), ''];
		$parts[] = '---';
		$parts[] = 'Report Issue context:';
		foreach (['module', 'view', 'recordId', 'userAgent', 'screenSize', 'crmVersion'] as $key) {
			if (!empty($context[$key])) {
				$parts[] = $key . ': ' . $context[$key];
			}
		}

		return implode("\n", $parts);
	}

	private function buildUpdateLog(\App\Modules\Users\Models\Record $reporter, int $assignedUserId): string
	{
		$assigneeLabel = \App\Fields\Owner::getUserLabel($assignedUserId);
		$timestamp = date('l dS F Y h:i:s A');
		return 'Ticket created via Report Issue widget. Assigned to user '
			. $assigneeLabel
			. ' -- '
			. $timestamp
			. ' by '
			. $reporter->getName()
			. '--//--';
	}
}
