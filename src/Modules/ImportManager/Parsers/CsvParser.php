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

	public function __construct(string $filePath, array $options = [], ?DelimiterDetector $detector = null)
	{
		$this->filePath = $filePath;
		$this->delimiterDetector = $detector ?? new DelimiterDetector();
		$this->enclosure = $options['enclosure'] ?? '"';
		$this->delimiter = $options['delimiter'] ?? $this->delimiterDetector->detect($filePath);
		$this->encoding = $options['encoding'] ?? $this->detectEncoding();
	}

	public function readPreview(int $limit): array
	{
		$rows = [];
		$file = new \SplFileObject($this->filePath, 'r');
		$file->setCsvControl($this->delimiter, $this->enclosure);
		$file->setFlags(\SplFileObject::READ_AHEAD);

		$rowIndex = 0;
		while (!$file->eof()) {
			$row = $file->fgetcsv();
			if ($row === false || $row === null) {
				continue;
			}

			$normalized = $this->normalizeRow($row);
			if ($rowIndex === 0) {
				$this->headers = $this->buildHeaders($normalized);
				$rowIndex++;
				continue;
			}

			$rows[] = $this->alignRow($normalized);
			if (count($rows) >= $limit) {
				break;
			}
			$rowIndex++;
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
}

