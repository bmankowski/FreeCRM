<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Shared contract for ImportManager parsers.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Parsers;

interface ParserInterface
{
	/**
	 * @return array<int, array<int, string|null>>
	 */
	public function readPreview(int $limit): array;

	/**
	 * @return string[]
	 */
	public function getHeaders(): array;

	public function getMetadata(): array;

	/**
	 * Iterate over all rows available in the source file.
	 *
	 * @param callable $callback receives array<int, string|null> $row
	 */
	public function iterate(callable $callback): void;
}

