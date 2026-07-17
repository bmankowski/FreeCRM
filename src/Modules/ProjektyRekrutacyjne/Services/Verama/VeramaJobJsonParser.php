<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\ProjektyRekrutacyjne\Services\Verama;

final class VeramaJobJsonParser
{
	public static function parseFile(string $path): VeramaJobImportDto
	{
		$raw = file_get_contents($path);
		if ($raw === false) {
			throw new \RuntimeException('Cannot read JSON file: ' . $path);
		}

		$data = json_decode($raw, true);
		if (!is_array($data)) {
			throw new \RuntimeException('Invalid JSON in: ' . $path);
		}

		$source = (string) ($data['source'] ?? '');
		if ($source !== 'verama') {
			throw new \RuntimeException('Unsupported source "' . $source . '" in: ' . $path);
		}

		$externalId = trim((string) ($data['external_id'] ?? ''));
		if ($externalId === '') {
			throw new \RuntimeException('Missing external_id in: ' . $path);
		}

		$status = strtoupper(trim((string) ($data['status'] ?? '')));
		if ($status !== 'OPEN' && $status !== 'CLOSED') {
			throw new \RuntimeException('Unsupported status "' . $status . '" in: ' . $path);
		}

		$api = $data['api'] ?? null;
		if (!is_array($api)) {
			throw new \RuntimeException('Missing api object in: ' . $path);
		}

		$title = trim((string) ($api['title'] ?? ''));
		if ($title === '' && $status === 'OPEN') {
			throw new \RuntimeException('Missing api.title in: ' . $path);
		}

		$sections = $data['description_sections'] ?? [];
		if (!is_array($sections)) {
			$sections = [];
		}
		$normalizedSections = [];
		foreach ($sections as $key => $value) {
			if (is_string($key) && is_string($value) && $value !== '') {
				$normalizedSections[$key] = $value;
			}
		}

		$systemId = $data['system_id'] ?? $api['systemId'] ?? null;
		$systemId = $systemId !== null && $systemId !== '' ? (string) $systemId : null;

		$url = trim((string) ($data['url'] ?? ''));
		if ($url === '') {
			$url = 'https://app.verama.com/app/job-requests/' . $externalId;
		}

		$descriptionHtml = (string) ($data['description_html'] ?? $api['description'] ?? '');

		return new VeramaJobImportDto(
			jsonFilePath: $path,
			source: $source,
			externalId: $externalId,
			status: $status,
			systemId: $systemId,
			url: $url,
			scrapedAt: isset($data['scraped_at']) ? (string) $data['scraped_at'] : null,
			descriptionHtml: $descriptionHtml,
			descriptionText: (string) ($data['description_text'] ?? ''),
			descriptionSections: $normalizedSections,
			api: $api,
		);
	}
}
