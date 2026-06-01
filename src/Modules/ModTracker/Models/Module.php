<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * ModTracker is a utility module; its UI lives under Settings.
 */

declare(strict_types=1);

namespace App\Modules\ModTracker\Models;

class Module extends \App\Modules\Base\Models\Module
{
	public function getDefaultUrl(): string
	{
		return 'index.php?module=' . $this->getName() . '&parent=Settings&view=ListView';
	}
}
