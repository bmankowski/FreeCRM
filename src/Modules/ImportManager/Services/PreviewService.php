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
		$previewRows = isset($options['preview_rows']) && $options['preview_rows'] > 0
			? (int) $options['preview_rows']
			: $this->config->getPreviewRows();
		$rows = $parser->readPreview($previewRows);

		return [
			'headers' => $parser->getHeaders(),
			'rows' => $rows,
			'meta' => $parser->getMetadata(),
		];
	}
}

