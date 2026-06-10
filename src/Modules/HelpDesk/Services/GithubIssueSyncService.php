<?php

namespace App\Modules\HelpDesk\Services;

/**
 * Creates GitHub issues for HelpDesk tickets reported via the Report Issue widget.
 */
class GithubIssueSyncService
{
	public function createForTicket(
		\App\Modules\Base\Models\Record $record,
		array $context,
		string $screenshotFilename,
		\App\Modules\Users\Models\Record $reporter
	): ?object {
		$client = \App\Modules\Settings\Github\Models\Client::getInstance();
		if (!$client->isAuthorized()) {
			return null;
		}

		$labels = \App\Core\AppConfig::module('ReportIssue', 'github_labels');
		if (!is_array($labels)) {
			$labels = ['bug', 'user-report'];
		}

		$prefix = (string) (\App\Core\AppConfig::module('ReportIssue', 'github_issue_prefix') ?: '[User Report]');
		$ticketNo = (string) $record->get('ticket_no');
		$userTitle = (string) $record->get('ticket_title');
		$issueTitle = trim($prefix . ' ' . $ticketNo . ': ' . $userTitle);

		$body = $this->buildIssueBody($record, $context, $screenshotFilename, $reporter);
		$response = $client->createIssue($issueTitle, $body, $labels);
		if ($response === false) {
			\App\Log\Log::error('ReportIssue: GitHub createIssue failed for ticket ' . $record->getId());
			return null;
		}

		return $response;
	}

	private function buildIssueBody(
		\App\Modules\Base\Models\Record $record,
		array $context,
		string $screenshotFilename,
		\App\Modules\Users\Models\Record $reporter
	): string {
		$siteUrl = rtrim((string) \App\Core\AppConfig::main('site_URL'), '/');
		$ticketId = (int) $record->getId();
		$ticketUrl = $siteUrl . '/index.php?module=HelpDesk&view=Detail&record=' . $ticketId;
		$ticketNo = (string) $record->get('ticket_no');
		$userDescription = trim((string) ($context['userDescription'] ?? ''));
		$submittedAt = gmdate('Y-m-d H:i:s') . ' UTC';

		$version = '';
		$versionData = include ROOT_DIRECTORY . '/config/version.php';
		if (is_array($versionData) && isset($versionData['appVersion'])) {
			$version = (string) $versionData['appVersion'];
		}

		$pageUrl = (string) ($context['pageUrl'] ?? 'n/a');
		$module = (string) ($context['module'] ?? 'n/a');
		$view = (string) ($context['view'] ?? 'n/a');
		$recordId = (string) ($context['recordId'] ?? 'n/a');
		$parentId = (string) ($record->get('parent_id') ?: 'n/a');
		$userAgent = (string) ($context['userAgent'] ?? 'n/a');
		$screenSize = (string) ($context['screenSize'] ?? 'n/a');

		$lines = [
			'## Opis zgłoszenia',
			'',
			$userDescription !== '' ? $userDescription : '_Brak opisu._',
			'',
			'---',
			'',
			'## Ticket CRM',
			'',
			'| Pole | Wartość |',
			'|------|---------|',
			'| Ticket | [' . $ticketNo . '](' . $ticketUrl . ') |',
			'| Tytuł | ' . $this->escapeTableCell((string) $record->get('ticket_title')) . ' |',
			'| Zgłaszający | ' . $this->escapeTableCell($reporter->getName() . ' (' . $reporter->get('email1') . ')') . ' |',
			'| Data | ' . $submittedAt . ' |',
			'',
			'## Kontekst strony',
			'',
			'| Pole | Wartość |',
			'|------|---------|',
			'| URL | ' . $this->escapeTableCell($pageUrl) . ' |',
			'| Moduł | ' . $this->escapeTableCell($module) . ' |',
			'| Widok | ' . $this->escapeTableCell($view) . ' |',
			'| Rekord | ' . $this->escapeTableCell($recordId) . ' |',
			'| Powiązany rekord (parent_id) | ' . $this->escapeTableCell($parentId) . ' |',
			'',
			'## Środowisko',
			'',
			'| Pole | Wartość |',
			'|------|---------|',
			'| FreeCRM | ' . $this->escapeTableCell($version) . ' |',
			'| PHP | ' . PHP_VERSION . ' |',
			'| Przeglądarka | ' . $this->escapeTableCell($userAgent) . ' |',
			'| Rozdzielczość | ' . $this->escapeTableCell($screenSize) . ' |',
			'| Instancja | ' . $this->escapeTableCell($siteUrl) . ' |',
			'',
			'## Screenshot',
			'',
			'Załącznik w CRM: **' . $this->escapeTableCell($screenshotFilename) . '**',
			'',
			'> Screenshot nie jest embedowany w GitHubie — otwórz ticket CRM powyżej.',
			'',
			'---',
			'',
			'_Auto-generated from FreeCRM Report Issue widget._',
		];

		return implode("\n", $lines);
	}

	private function escapeTableCell(string $value): string
	{
		return str_replace('|', '\\|', $value);
	}
}
