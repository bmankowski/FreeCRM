<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * CSV parser used by ImportManager.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Parsers;

class CsvParser implements ParserInterface
{
	private string $filePath;
	private string $delimiter;
	private string $enclosure;
	private string $encoding;
	private array $headers = [];
	private DelimiterDetector $delimiterDetector;
	private bool $headersLoaded = false;

	public function __construct(string $filePath, array $options = [], ?DelimiterDetector $detector = null)
	{
		$this->filePath = $filePath;
		$this->delimiterDetector = $detector ?? new DelimiterDetector();
		$this->enclosure = $options['enclosure'] ?? '"';
		
		// Handle empty delimiter (Auto detection) - must be a single character for setCsvControl
		$delimiter = $options['delimiter'] ?? '';
		if (empty($delimiter)) {
			$this->delimiter = $this->delimiterDetector->detect($filePath);
		} else {
			// Handle special cases like "\t" (tab) - convert string representation to actual character
			if ($delimiter === '\t' || $delimiter === '\\t') {
				$this->delimiter = "\t";
			} else {
				// Ensure delimiter is a single character
				$this->delimiter = substr($delimiter, 0, 1);
			}
		}
		
		$this->encoding = $options['encoding'] ?? $this->detectEncoding();
	}

	public function readPreview(int $limit): array
	{
		$this->ensureHeadersLoaded();
		$rows = [];
		$file = $this->createFileObject();
		$this->skipHeader($file);

		while (!$file->eof()) {
			$row = $file->fgetcsv();
			if ($row === false || $row === null) {
				continue;
			}
			$normalized = $this->alignRow($this->normalizeRow($row));
			if ($this->isRowEmpty($normalized)) {
				continue;
			}
			$rows[] = $normalized;
			if (count($rows) >= $limit) {
				break;
			}
		}

		return $rows;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getMetadata(): array
	{
		return [
			'delimiter' => $this->delimiter,
			'enclosure' => $this->enclosure,
			'encoding' => $this->encoding,
		];
	}

	public function iterate(callable $callback): void
	{
		$this->ensureHeadersLoaded();
		$file = $this->createFileObject();
		$this->skipHeader($file);

		while (!$file->eof()) {
			$row = $file->fgetcsv();
			if ($row === false || $row === null) {
				continue;
			}
			$normalized = $this->alignRow($this->normalizeRow($row));
			if ($this->isRowEmpty($normalized)) {
				continue;
			}
			$callback($normalized);
		}
	}

	private function detectEncoding(): string
	{
		$sample = file_get_contents($this->filePath, false, null, 0, 4096);
		if ($sample === false) {
			return 'UTF-8';
		}
		$detected = mb_detect_encoding($sample, ['UTF-8', 'Windows-1250', 'ISO-8859-2', 'ISO-8859-1', 'CP1250', 'CP1252'], true);
		return $detected ?: 'UTF-8';
	}

	private function normalizeRow(array $row): array
	{
		return array_map(function ($value) {
			if ($value === null) {
				return null;
			}
			$value = (string) $value;
			if ($this->encoding !== 'UTF-8') {
				$value = mb_convert_encoding($value, 'UTF-8', $this->encoding);
			}
			return trim($value);
		}, $row);
	}

	private function buildHeaders(array $row): array
	{
		$headers = [];
		foreach ($row as $index => $value) {
			$value = $value === '' ? 'Column ' . ($index + 1) : $value;
			$headers[$index] = $value;
		}
		return $headers;
	}

	private function alignRow(array $row): array
	{
		$expectedColumns = count($this->headers);
		$currentColumns = count($row);

		if ($expectedColumns === 0) {
			return $row;
		}

		if ($currentColumns < $expectedColumns) {
			$row = array_pad($row, $expectedColumns, null);
		} elseif ($currentColumns > $expectedColumns) {
			$row = array_slice($row, 0, $expectedColumns);
		}

		return $row;
	}

	private function isRowEmpty(array $row): bool
	{
		foreach ($row as $value) {
			if ($value !== null && $value !== '') {
				return false;
			}
		}
		return true;
	}

	private function createFileObject(): \SplFileObject
	{
		$file = new \SplFileObject($this->filePath, 'r');
		$delimiter = mb_strlen($this->delimiter) > 0 ? mb_substr($this->delimiter, 0, 1) : ',';
		$enclosure = mb_strlen($this->enclosure) > 0 ? mb_substr($this->enclosure, 0, 1) : '"';
		$file->setCsvControl($delimiter, $enclosure);
		// Do not enable READ_AHEAD – it causes the first data row to be skipped after rewinding.
		return $file;
	}

	private function ensureHeadersLoaded(): void
	{
		if ($this->headersLoaded) {
			return;
		}
		$file = $this->createFileObject();
		if (!$file->eof()) {
			$row = $file->fgetcsv();
			if ($row !== false && $row !== null) {
				$this->headers = $this->buildHeaders($this->normalizeRow($row));
			}
		}
		$this->headersLoaded = true;
	}

	private function skipHeader(\SplFileObject $file): void
	{
		$file->rewind();
		$file->fgetcsv();
	}
}

