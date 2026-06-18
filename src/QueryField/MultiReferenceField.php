<?php

namespace App\QueryField;

/**
 * Query conditions for comma-separated multi-reference CRM IDs (uitype 306).
 */
class MultiReferenceField extends BaseField
{

	/**
	 * @return int[]
	 */
	protected function getIdValues(): array
	{
		if ($this->value === '' || $this->value === null) {
			return [];
		}
		$values = [];
		foreach (explode(',', (string) $this->value) as $part) {
			$id = (int) trim($part);
			if ($id > 0) {
				$values[] = $id;
			}
		}
		return $values;
	}

	/**
	 * @param int $id
	 * @return array
	 */
	protected function idMatchCondition(int $id): array
	{
		$column = $this->getColumnName();
		return [
			'or',
			[$column => (string) $id],
			['like', $column, $id . ',%', false],
			['like', $column, '%,' . $id . ',%', false],
			['like', $column, '%,' . $id, false],
		];
	}

	public function operatorE()
	{
		$values = $this->getIdValues();
		if (empty($values)) {
			return [];
		}
		$condition = ['or'];
		foreach ($values as $id) {
			$condition[] = $this->idMatchCondition($id);
		}
		return $condition;
	}

	public function operatorN()
	{
		$values = $this->getIdValues();
		if (empty($values)) {
			return [];
		}
		$condition = ['and'];
		foreach ($values as $id) {
			$condition[] = ['not', $this->idMatchCondition($id)];
		}
		return $condition;
	}

	public function operatorC()
	{
		return $this->operatorE();
	}

	public function operatorY()
	{
		return ['or', [$this->getColumnName() => ''], [$this->getColumnName() => null]];
	}

	public function operatorNy()
	{
		return ['and', ['<>', $this->getColumnName(), ''], ['not', [$this->getColumnName() => null]]];
	}
}
