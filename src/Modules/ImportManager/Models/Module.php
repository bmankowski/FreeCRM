<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * ImportManager module model.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Models;

class Module extends \App\Modules\Base\Models\Module
{
	public function getDefaultUrl(): string
	{
		return 'index.php?module=' . $this->getName() . '&view=Wizard';
	}
}

