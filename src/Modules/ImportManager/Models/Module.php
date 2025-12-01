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
	/**
	 * Function to get the Default View Component Name
	 * @return string
	 */
	public function getDefaultViewName()
	{
		return 'Wizard';
	}

	public function getDefaultUrl(): string
	{
		return 'index.php?module=' . $this->getName() . '&view=' . $this->getDefaultViewName();
	}
}

