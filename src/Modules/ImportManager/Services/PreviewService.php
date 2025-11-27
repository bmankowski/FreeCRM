<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Builds preview payload for ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\ImportManager\Parsers\ParserFactory;

class PreviewService
{
	private ConfigProvider $config;
	private ParserFactory $parserFactory;

	public function __construct(?ConfigProvider $config = null, ?ParserFactory $parserFactory = null)
	{
		$this->config = $config ?? new ConfigProvider();
		$this->parserFactory = $parserFactory ?? new ParserFactory($this->config);
	}

	public function build(string $format, string $absolutePath, array $options = []): array
	{
		$parser = $this->parserFactory->create($format, $absolutePath, $options);
		$rows = $parser->readPreview($this->config->getPreviewRows());

		return [
			'headers' => $parser->getHeaders(),
			'rows' => $rows,
			'meta' => $parser->getMetadata(),
		];
	}
}

