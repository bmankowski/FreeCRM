<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * XML parser used by ImportManager for preview purposes.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Parsers;

class XmlParser implements ParserInterface
{
	private string $filePath;
	private string $recordPath;
	private array $headers = [];

	public function __construct(string $filePath, array $options = [])
	{
		$this->filePath = $filePath;
		$this->recordPath = $options['xpath'] ?? '';

		if ($this->recordPath === '') {
			throw new \InvalidArgumentException('Wymagane jest podanie XPath rekordu dla importu XML.');
		}
	}

	public function readPreview(int $limit): array
	{
		$reader = new \XMLReader();
		if (!$reader->open($this->filePath, null, LIBXML_NONET | LIBXML_COMPACT)) {
			throw new \RuntimeException('Nie można otworzyć pliku XML.');
		}

		$rows = [];
		$pathStack = [];
		$targetSegments = $this->normalizePath($this->recordPath);

		while ($reader->read()) {
			if ($reader->nodeType === \XMLReader::ELEMENT) {
				$pathStack[$reader->depth] = $reader->localName;
				if ($this->isPathMatch($pathStack, $targetSegments)) {
					$row = $this->extractCurrentNode($reader);
					$rows[] = $row;
					if (count($rows) >= $limit) {
						break;
					}
				}
			} elseif ($reader->nodeType === \XMLReader::END_ELEMENT) {
				unset($pathStack[$reader->depth]);
			}
		}

		$reader->close();
		return $rows;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getMetadata(): array
	{
		return [
			'xpath' => $this->recordPath,
		];
	}

	public function iterate(callable $callback): void
	{
		$reader = new \XMLReader();
		if (!$reader->open($this->filePath, null, LIBXML_NONET | LIBXML_COMPACT)) {
			throw new \RuntimeException('Nie można otworzyć pliku XML.');
		}

		$pathStack = [];
		$targetSegments = $this->normalizePath($this->recordPath);

		while ($reader->read()) {
			if ($reader->nodeType === \XMLReader::ELEMENT) {
				$pathStack[$reader->depth] = $reader->localName;
				if ($this->isPathMatch($pathStack, $targetSegments)) {
					$row = $this->extractCurrentNode($reader);
					$callback($row);
				}
			} elseif ($reader->nodeType === \XMLReader::END_ELEMENT) {
				unset($pathStack[$reader->depth]);
			}
		}

		$reader->close();
	}

	private function normalizePath(string $path): array
	{
		return array_values(array_filter(explode('/', $path)));
	}

	private function isPathMatch(array $currentPath, array $targetPath): bool
	{
		$normalized = array_values($currentPath);
		if (count($normalized) < count($targetPath)) {
			return false;
		}

		$endSlice = array_slice($normalized, -count($targetPath));
		return $endSlice === $targetPath;
	}

	private function extractCurrentNode(\XMLReader $reader): array
	{
		$dom = new \DOMDocument();
		$node = $reader->expand();
		if (!$node) {
			return [];
		}
		$domNode = $dom->importNode($node, true);
		$dom->appendChild($domNode);
		$simple = simplexml_import_dom($domNode);
		$data = $this->flattenNode($simple, '', true);

		foreach (array_keys($data) as $key) {
			if (!in_array($key, $this->headers, true)) {
				$this->headers[] = $key;
			}
		}

		return $this->alignRow($data);
	}

	private function flattenNode(\SimpleXMLElement $node, string $prefix = '', bool $isRoot = false): array
	{
		$attributes = $node->attributes();
		$children = $node->children();

		// FreeCRM export convention: a leaf field carries its display label in the
		// `label` attribute and the value as text content. Collapse both into one
		// column whose header is the label and whose cell is the value.
		if (count($children) === 0 && isset($attributes['label'])) {
			return [trim((string) $attributes['label']) => trim((string) $node)];
		}

		$result = [];
		foreach ($attributes as $name => $value) {
			if ($isRoot && $name === 'module') {
				continue;
			}
			$key = trim($prefix . '@' . $name, '.');
			$result[$key] = (string) $value;
		}

		if (count($children) === 0) {
			$result[$prefix !== '' ? $prefix : 'value'] = trim((string) $node);
		} else {
			foreach ($children as $name => $child) {
				$childPrefix = $prefix === '' ? $name : $prefix . '.' . $name;
				$result += $this->flattenNode($child, $childPrefix);
			}
		}

		return $result;
	}

	private function alignRow(array $data): array
	{
		if (!$this->headers) {
			return $data;
		}

		$aligned = [];
		foreach ($this->headers as $header) {
			$aligned[] = $data[$header] ?? null;
		}

		return $aligned;
	}
}

