<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Represents a single mapping definition prepared in the ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;

class MappingDefinition
{
	private ModuleModel $module;
	private int $batchId;
	private array $mapping = [];
	private array $defaultValues = [];
	private array $duplicateSets = [
		'required' => [],
		'optional' => [],
	];
	private array $sourceHeaders = [];
	private array $options = [];
	private string $duplicateStrategy = 'skip';
	/** @var array<string, bool> */
	private array $mappedFieldIndex = [];
	/** @var array<string, \App\Modules\Base\Models\Field>|null */
	private ?array $fieldsMap = null;

	private function __construct(ModuleModel $module, int $batchId)
	{
		$this->module = $module;
		$this->batchId = $batchId;
	}

	public static function fromPayload(
		array $payload,
		ModuleModel $moduleModel,
		ConfigProvider $configProvider
	): self {
		$batchId = (int) ($payload['batchId'] ?? 0);
		if ($batchId <= 0) {
			throw new \RuntimeException('Nieprawidłowy identyfikator wsadu.');
		}

		$self = new self($moduleModel, $batchId);
		$self->sourceHeaders = static::normalizeHeaders($payload['sourceHeaders'] ?? []);
		$self->hydrateMappingRows($payload['mapping'] ?? []);
		$self->hydrateDefaultValues($payload['defaultValues'] ?? []);
		$self->hydrateDuplicateSets($payload['duplicateSets'] ?? [], $configProvider->getDuplicateConfig($moduleModel->getName()));
		$self->hydrateOptions();
		$self->setDuplicateStrategy((string) ($payload['duplicateStrategy'] ?? 'skip'));
		$self->assertMandatoryFieldsCovered();

		return $self;
	}

	public function getBatchId(): int
	{
		return $this->batchId;
	}

	public function getModuleName(): string
	{
		return $this->module->getName();
	}

	public function getMapping(): array
	{
		return $this->mapping;
	}

	public function getDefaultValues(): array
	{
		return $this->defaultValues;
	}

	public function getDuplicateSets(): array
	{
		return $this->duplicateSets;
	}

	public function getOptions(): array
	{
		return $this->options;
	}

	public function getDuplicateStrategy(): string
	{
		return $this->duplicateStrategy;
	}

	private static function normalizeHeaders($rawHeaders): array
	{
		if (!is_array($rawHeaders)) {
			return [];
		}
		return array_values(array_map(static fn($header) => trim((string) $header), $rawHeaders));
	}

	private function hydrateMappingRows($rows): void
	{
		if (!is_array($rows)) {
			$rows = [];
		}
		$fields = $this->getFieldsMap();
		$this->mapping = [];
		$this->mappedFieldIndex = [];

		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}

			$fieldName = trim((string) ($row['field'] ?? ''));
			if ($fieldName === '') {
				continue;
			}

			$fieldModel = $fields[strtolower($fieldName)] ?? null;
			if (!$fieldModel || !$fieldModel->isEditable()) {
				throw new \RuntimeException(sprintf('Pole %s nie jest dostępne do mapowania.', $fieldName));
			}

			$normalizedField = strtolower($fieldModel->getName());
			if (isset($this->mappedFieldIndex[$normalizedField])) {
				throw new \RuntimeException(sprintf('Pole %s zostało przypisane wielokrotnie.', $fieldName));
			}

			$this->mappedFieldIndex[$normalizedField] = true;

			$this->mapping[] = [
				'index' => isset($row['index']) ? (int) $row['index'] : null,
				'column' => trim((string) ($row['column'] ?? '')),
				'field' => $fieldModel->getName(),
				'label' => \App\Language::translate($fieldModel->getFieldLabel(), $this->module->getName()),
			];
		}

		if (count($this->mapping) === 0) {
			throw new \RuntimeException('Mapowanie musi zawierać co najmniej jedno pole.');
		}
	}

	private function hydrateDefaultValues($entries): void
	{
		$this->defaultValues = [];
		if (!is_array($entries)) {
			return;
		}

		foreach ($entries as $fieldName => $value) {
			$fieldName = trim((string) $fieldName);
			if ($fieldName === '') {
				continue;
			}
			$fieldKey = strtolower($fieldName);
			$fields = $this->getFieldsMap();
			if (!array_key_exists($fieldKey, $fields)) {
				throw new \RuntimeException(sprintf('Pole %s jest niepoprawne w sekcji wartości domyślnych.', $fieldName));
			}
			if ($value === null || $value === '') {
				continue;
			}
			$this->defaultValues[$fields[$fieldKey]->getName()] = $value;
		}
	}

	private function hydrateDuplicateSets($payload, array $config): void
	{
		$this->duplicateSets['required'] = array_values($config['requiredSets'] ?? []);
		$this->duplicateSets['optional'] = [];

		$optionalIndexes = [];
		if (is_array($payload) && isset($payload['optionalActive'])) {
			$optionalIndexes = array_map('intval', (array) $payload['optionalActive']);
		}

		$optionalSets = array_values($config['optionalSets'] ?? []);
		foreach ($optionalIndexes as $index) {
			if (isset($optionalSets[$index])) {
				$this->duplicateSets['optional'][] = $optionalSets[$index];
			}
		}

		$this->options['duplicateConfig'] = [
			'required' => $this->duplicateSets['required'],
			'optionalAvailable' => $optionalSets,
			'mergeKeys' => array_values($config['mergeKeys'] ?? []),
		];
	}

	private function hydrateOptions(): void
	{
		$mappedColumns = array_map(static fn($row) => $row['column'], $this->mapping);
		$this->options['sourceHeaders'] = $this->sourceHeaders;
		$this->options['unmappedColumns'] = array_values(array_diff($this->sourceHeaders, $mappedColumns));
		$this->options['generatedAt'] = date('c');
	}

	private function setDuplicateStrategy(string $strategy): void
	{
		$strategy = strtolower($strategy);
		$allowed = ['skip', 'overwrite', 'merge'];
		if (!in_array($strategy, $allowed, true)) {
			throw new \RuntimeException('Nieprawidłowa strategia obsługi duplikatów.');
		}
		$this->duplicateStrategy = $strategy;
	}

	private function assertMandatoryFieldsCovered(): void
	{
		$mandatoryMissing = [];
		foreach ($this->getFieldsMap() as $fieldModel) {
			if (!$fieldModel->isMandatory()) {
				continue;
			}
			if (!$fieldModel->isEditable()) {
				continue;
			}

			$fieldName = $fieldModel->getName();
			if (!$this->isFieldSatisfied($fieldModel->getName())) {
				$mandatoryMissing[] = \App\Language::translate($fieldModel->getFieldLabel(), $this->module->getName());
			}
		}

		foreach ($this->duplicateSets['required'] as $set) {
			foreach ((array) $set as $fieldName) {
				if (!$this->isFieldSatisfied($fieldName)) {
					$mandatoryMissing[] = $fieldName;
				}
			}
		}

		if ($mandatoryMissing) {
			throw new \RuntimeException('Brakuje wartości dla pól obowiązkowych: ' . implode(', ', array_unique($mandatoryMissing)));
		}
	}

	private function isFieldSatisfied(string $fieldName): bool
	{
		$fieldName = strtolower($fieldName);
		if (isset($this->mappedFieldIndex[$fieldName])) {
			return true;
		}

		foreach ($this->defaultValues as $defaultField => $_) {
			if (strtolower($defaultField) === $fieldName) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array<string, \App\Modules\Base\Models\Field>
	 */
	private function getFieldsMap(): array
	{
		if ($this->fieldsMap !== null) {
			return $this->fieldsMap;
		}

		$this->fieldsMap = [];
		foreach ($this->module->getFields() as $fieldModel) {
			$this->fieldsMap[strtolower($fieldModel->getName())] = $fieldModel;
		}
		return $this->fieldsMap;
	}
}

