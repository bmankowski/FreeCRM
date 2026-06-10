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

namespace App\ModuleManagement\Services;

/**
 * LanguageService class.
 *
 * Service for language file operations.
 */
class LanguageService
{
	private const STANDARD_KEYS = [
		'LBL_BASIC_INFORMATION',
		'LBL_CUSTOM_INFORMATION',
		'FL_NUMBER',
	];

	private const PICKLIST_UITYPES = [15, 16, 33];

	/**
	 * Create default JSON language files for a new module.
	 *
	 * @param array<string, string> $extraStrings
	 */
	public function createForModule(string $moduleName, string $moduleLabel, array $extraStrings = []): void
	{
		foreach ($this->getActiveLanguages() as $lang) {
			$languageStrings = [
				$moduleName => $moduleLabel,
				'SINGLE_' . $moduleName => $moduleLabel,
			];

			foreach (self::STANDARD_KEYS as $key) {
				$translated = \App\Runtime\Vtiger_Language_Handler::getLanguageTranslatedString($lang, $key, 'Vtiger');
				if ($translated !== null) {
					$languageStrings[$key] = $translated;
				}
			}

			foreach ($extraStrings as $key => $value) {
				if ($key !== '' && $value !== '') {
					$languageStrings[$key] = $value;
				}
			}

			$this->writeModuleLanguageFile($lang, $moduleName, $languageStrings, true);
		}

		$this->syncModuleFieldLabels($moduleName);
	}

	/**
	 * Register all field labels and picklist values from DB in the module language files.
	 */
	public function syncModuleFieldLabels(string $moduleName): void
	{
		$moduleId = \App\Utils\ModuleUtils::getModuleId($moduleName);
		if (!$moduleId) {
			return;
		}

		$fields = (new \App\Db\Query())
			->select(['fieldname', 'fieldlabel', 'uitype'])
			->from('vtiger_field')
			->where(['tabid' => $moduleId])
			->all();

		foreach ($fields as $field) {
			$label = trim((string) $field['fieldlabel']);
			if ($label !== '') {
				$this->ensureTranslationKey($moduleName, $label, $label);
			}

			if (!in_array((int) $field['uitype'], self::PICKLIST_UITYPES, true)) {
				continue;
			}

			$picklistValues = \App\Fields\Picklist::getPickListValues((string) $field['fieldname']);
			foreach ($picklistValues as $value) {
				$this->ensureTranslationKey($moduleName, $value, $this->humanizeLanguageKey($value));
			}
		}
	}

	/**
	 * Ensure a translation key exists in the related module language files.
	 */
	public function ensureTranslationKey(string $moduleName, string $key, ?string $defaultValue = null): void
	{
		$key = trim($key);
		if ($key === '') {
			return;
		}

		$defaultValue = $defaultValue ?? $this->humanizeLanguageKey($key);

		foreach ($this->getActiveLanguages() as $lang) {
			$existing = $this->readModuleLanguageFile($lang, $moduleName);
			if (isset($existing['languageStrings'][$key])) {
				continue;
			}

			$value = \App\Runtime\Vtiger_Language_Handler::getLanguageTranslatedString($lang, $key, 'Vtiger')
				?? \App\Runtime\Vtiger_Language_Handler::getLanguageTranslatedString($lang, $key, $moduleName)
				?? $defaultValue;

			$this->writeModuleLanguageFile($lang, $moduleName, [$key => $value], true);
		}
	}

	/**
	 * Delete language files for a module.
	 */
	public function deleteForModule(string $moduleName): void
	{
		foreach ($this->getActiveLanguages() as $lang) {
			$langFilePath = ROOT_DIRECTORY . "/languages/$lang/{$moduleName}.json";
			if (file_exists($langFilePath)) {
				unlink($langFilePath);
			}

			$langFilePath = ROOT_DIRECTORY . "/languages/$lang/Settings/{$moduleName}.json";
			if (file_exists($langFilePath)) {
				unlink($langFilePath);
			}
		}
	}

	/**
	 * @return string[]
	 */
	private function getActiveLanguages(): array
	{
		$languages = (new \App\Db\Query())
			->select(['prefix'])
			->from('vtiger_language')
			->where(['active' => 1])
			->column();

		return $languages ?: ['en_us', 'pl_pl'];
	}

	/**
	 * @return array{languageStrings: array<string, string>, jsLanguageStrings: array<string, string>}
	 */
	private function readModuleLanguageFile(string $lang, string $moduleName): array
	{
		$data = [
			'languageStrings' => [],
			'jsLanguageStrings' => [],
		];

		$filePath = ROOT_DIRECTORY . "/languages/$lang/{$moduleName}.json";
		if (!file_exists($filePath)) {
			return $data;
		}

		$decoded = json_decode((string) file_get_contents($filePath), true);
		if (!is_array($decoded)) {
			return $data;
		}

		$data['languageStrings'] = isset($decoded['languageStrings']) && is_array($decoded['languageStrings'])
			? $decoded['languageStrings']
			: [];
		$data['jsLanguageStrings'] = isset($decoded['jsLanguageStrings']) && is_array($decoded['jsLanguageStrings'])
			? $decoded['jsLanguageStrings']
			: [];

		return $data;
	}

	/**
	 * @param array<string, string> $languageStrings
	 */
	private function writeModuleLanguageFile(string $lang, string $moduleName, array $languageStrings, bool $merge = false): void
	{
		$dir = ROOT_DIRECTORY . "/languages/$lang";
		if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
			throw new \App\Exceptions\AppException('Cannot create language directory: ' . $dir);
		}

		$filePath = $dir . DIRECTORY_SEPARATOR . $moduleName . '.json';
		$data = $merge ? $this->readModuleLanguageFile($lang, $moduleName) : [
			'languageStrings' => [],
			'jsLanguageStrings' => [],
		];

		foreach ($languageStrings as $key => $value) {
			$data['languageStrings'][$key] = $value;
		}

		ksort($data['languageStrings']);

		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			throw new \App\Exceptions\AppException('Cannot encode language file: ' . $filePath);
		}

		$json .= "\n";
		if (file_put_contents($filePath, $json) === false) {
			throw new \App\Exceptions\AppException('Cannot write language file: ' . $filePath);
		}
	}

	private function humanizeLanguageKey(string $key): string
	{
		if (str_starts_with($key, 'LBL_') || str_starts_with($key, 'FL_')) {
			$text = substr($key, (int) strpos($key, '_') + 1);
			$text = str_replace('_', ' ', $text);

			return ucwords(strtolower($text));
		}

		return $key;
	}
}
