<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Factory for building parser instances.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Parsers;

use App\Modules\ImportManager\Services\ConfigProvider;

class ParserFactory
{
	private ConfigProvider $config;
	private DelimiterDetector $delimiterDetector;

	public function __construct(?ConfigProvider $config = null, ?DelimiterDetector $delimiterDetector = null)
	{
		$this->config = $config ?? new ConfigProvider();
		$this->delimiterDetector = $delimiterDetector ?? new DelimiterDetector();
	}

	public function create(string $format, string $filePath, array $options = []): ParserInterface
	{
		$format = strtolower($format);
		switch ($format) {
			case 'csv':
				return new CsvParser($filePath, $options, $this->delimiterDetector);
			case 'xml':
				return new XmlParser($filePath, $options);
			default:
				throw new \InvalidArgumentException('Nieobsługiwany format importu: ' . $format);
		}
	}
}

