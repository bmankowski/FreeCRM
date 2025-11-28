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
	private DuplicateRuleRepository $duplicateRules;

	public function __construct(?array $config = null, ?DuplicateRuleRepository $duplicateRules = null)
	{
		$this->config = $config ?? $this->loadFromFile();
		$this->duplicateRules = $duplicateRules ?? new DuplicateRuleRepository();
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
		$moduleModel = ModuleModel::getInstance($moduleName);
		$savedSets = $moduleModel ? $this->duplicateRules->find($moduleName) : [];
		$suggestedSets = $moduleModel ? $this->duplicateRules->suggest($moduleModel) : [];

		return [
			'activeSets' => $savedSets,
			'suggestedSets' => $suggestedSets,
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
}

