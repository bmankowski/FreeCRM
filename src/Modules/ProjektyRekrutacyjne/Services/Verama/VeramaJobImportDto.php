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

final class VeramaJobImportDto
{
	/**
	 * @param array<string, mixed> $api
	 * @param array<string, string> $descriptionSections
	 */
	public function __construct(
		public readonly string $jsonFilePath,
		public readonly string $source,
		public readonly string $externalId,
		public readonly string $status,
		public readonly ?string $systemId,
		public readonly string $url,
		public readonly ?string $scrapedAt,
		public readonly string $descriptionHtml,
		public readonly string $descriptionText,
		public readonly array $descriptionSections,
		public readonly array $api,
	) {
	}
}
