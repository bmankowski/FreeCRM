<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * author bmankowski@gmail.com
 * copyright (c) FreeCRM
 * license FreeCRM Public License 1.0
 */

declare(strict_types=1);

/**
 * Minimal replacement for legacy Vtiger_StringTemplate.
 */
class Vtiger_StringTemplate
{
	/** @var array Template variables */
	public array $tplvars = [];

	/** @var string Pattern for variables */
	public string $_lookfor = '/\$([^\$]+)\$/';

	public function assign($key, $value): void
	{
		$this->tplvars[$key] = $value;
	}

	public function get($key)
	{
		return $this->tplvars[$key] ?? false;
	}

	public function clear($exceptvars = false): void
	{
		$restorevars = [];
		if ($exceptvars) {
			foreach ($exceptvars as $varkey) {
				$restorevars[$varkey] = $this->get($varkey);
			}
		}
		$this->tplvars = [];
		foreach ($restorevars as $key => $val) {
			$this->assign($key, $val);
		}
	}

	public function merge($instring, $avoidLookup = false)
	{
		if (empty($instring)) {
			return $instring;
		}
		if (!$avoidLookup) {
			$matches = [];
			preg_match_all($this->_lookfor, $instring, $matches);
			$matchcount = count($matches[1]);
			for ($index = 0; $index < $matchcount; ++$index) {
				$matchstr = $matches[0][$index];
				$matchkey = $matches[1][$index];
				$matchstr_regex = $this->__formatAsRegex($matchstr);
				$replacewith = $this->get($matchkey);
				if ($replacewith && !is_array($replacewith)) {
					$instring = preg_replace("/$matchstr_regex/", (string) $replacewith, $instring);
				}
			}
		}
		return $instring;
	}

	protected function __formatAsRegex($value)
	{
		$value = preg_replace('/\//', '\\/', (string) $value);
		$value = preg_replace('/(?<!\\\)\$/', '\\\\$', $value);
		return $value;
	}
}

