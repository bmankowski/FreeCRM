<?php

namespace App\Modules\Reports;

 */

class ReportRunQueryDependencyMatrix {

	protected $matrix = array();
	protected $computedMatrix = null;

	public function setDependency($table, array $dependents)
	{
		$this->matrix[$table] = $dependents;
	}

	public function addDependency($table, $dependent)
	{
		if (isset($this->matrix[$table]) && !in_array($dependent, $this->matrix[$table])) {
			$this->matrix[$table][] = $dependent;
		} else {
			$this->setDependency($table, array($dependent));
		}
	}

	public function getDependents($table)
	{
		$this->computeDependencies();
		return isset($this->computedMatrix[$table]) ? $this->computedMatrix[$table] : array();
	}

	protected function computeDependencies()
	{
		if ($this->computedMatrix !== null)
			return;

		$this->computedMatrix = array();
		foreach ($this->matrix as $key => $values) {
			$this->computedMatrix[$key] = $this->computeDependencyForKey($key, $values);
		}
	}

	protected function computeDependencyForKey($key, $values)
	{
		$merged = array();
		foreach ($values as $value) {
			$merged[] = $value;
			if (isset($this->matrix[$value])) {
				$merged = array_merge($merged, $this->matrix[$value]);
			}
		}
		return $merged;
	}
}