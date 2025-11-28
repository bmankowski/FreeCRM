<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Helper responsible for loading ImportManager configuration.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;

class ConfigProvider
{
	private array $config;
	private ?array $optionalConfig = null;
	private array $mandatorySetsCache = [];

	public function __construct(?array $config = null)
	{
		$this->config = $config ?? $this->loadFromFile();
	}

	public function get(string $path, $default = null)
	{
		$segments = explode('.', $path);
		$value = $this->config;
		foreach ($segments as $segment) {
			if (!is_array($value) || !array_key_exists($segment, $value)) {
				return $default;
			}
			$value = $value[$segment];
		}
		return $value;
	}

	public function getMaxUploadSizeBytes(): int
	{
		return (int) $this->get('fileLimits.maxUploadSizeBytes', 10 * 1024 * 1024);
	}

	public function getPreviewRows(): int
	{
		return (int) $this->get('preview.rows', 30);
	}

	public function getAllowedExtensions(): array
	{
		return (array) $this->get('fileLimits.allowedExtensions', ['csv', 'xml', 'zip']);
	}

	public function getRetentionDays(): int
	{
		return (int) $this->get('cleanup.retentionDays', 2);
	}

	public function getChunkSize(): int
	{
		return (int) $this->get('staging.chunkSize', 200);
	}

	public function getDuplicateConfig(string $moduleName): array
	{
		if ($this->optionalConfig === null) {
			$this->optionalConfig = $this->loadOptionalDuplicateConfig();
		}

		$config = $this->optionalConfig[$moduleName] ?? [];
		return [
			'requiredSets' => $this->resolveMandatorySets($moduleName),
			'optionalSets' => array_values($config['optionalSets'] ?? []),
			'mergeKeys' => array_values($config['mergeKeys'] ?? []),
		];
	}

	private function loadFromFile(): array
	{
		static $cached;

		if ($cached !== null) {
			return $cached;
		}

		$path = ROOT_DIRECTORY . '/config/modules/ImportManager.php';
		if (!is_file($path)) {
			return $cached = [];
		}

		$CONFIG = [];
		require $path;

		return $cached = $CONFIG ?? [];
	}

	private function loadOptionalDuplicateConfig(): array
	{
		$path = ROOT_DIRECTORY . '/config/import_duplicates.php';
		if (!is_file($path)) {
			return [];
		}

		$config = require $path;
		return is_array($config) ? $config : [];
	}

	private function resolveMandatorySets(string $moduleName): array
	{
		if (array_key_exists($moduleName, $this->mandatorySetsCache)) {
			return $this->mandatorySetsCache[$moduleName];
		}

		$moduleModel = ModuleModel::getInstance($moduleName);
		if (!$moduleModel) {
			return $this->mandatorySetsCache[$moduleName] = [];
		}

		$sets = [];
		foreach ($moduleModel->getFields() as $fieldModel) {
			if (!$fieldModel->isActiveField() || !$fieldModel->isEditable()) {
				continue;
			}
			if ($fieldModel->isMandatory()) {
				$sets[] = [$fieldModel->getName()];
			}
		}

		return $this->mandatorySetsCache[$moduleName] = $sets;
	}
}

