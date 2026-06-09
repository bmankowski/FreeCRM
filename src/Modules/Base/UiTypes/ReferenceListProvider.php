<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\Base\UiTypes;

interface ReferenceListProvider
{
	/**
	 * @return string[]
	 */
	public function getReferenceList(): array;
}
