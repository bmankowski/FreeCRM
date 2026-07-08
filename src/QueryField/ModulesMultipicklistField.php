<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\QueryField;

class ModulesMultipicklistField extends MultipicklistField
{
	public function operatorA()
	{
		return $this->operatorC();
	}

	public function operatorE()
	{
		return $this->operatorC();
	}
}
