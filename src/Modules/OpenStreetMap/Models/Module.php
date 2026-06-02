<?php

namespace App\Modules\OpenStreetMap\Models;

/**
 * Module Model
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use App\AppConfig;
class Module extends \App\Modules\Base\Models\Module
{

	public function getDefaultViewName()
	{
		return 'MapModal';
	}

	public function getDefaultUrl(): string
	{
		return 'index.php?module=' . $this->getName() . '&view=' . $this->getDefaultViewName();
	}

	/**
	 * @param int|string $mixed
	 * @return static|null
	 */
	public static function getInstance($mixed)
	{
		$instance = parent::getInstance($mixed);
		return $instance instanceof self ? $instance : null;
	}

	/**
	 * Check if module is allowed
	 * @param string $moduleName
	 * @return boolean
	 */
	public function isAllowModules($moduleName)
	{
		return in_array($moduleName, \App\Core\AppConfig::module($this->getName(), 'ALLOW_MODULES'));
	}

	/**
	 * Function to get allow modules with checking permissions
	 * @return array
	 */
	public function getAllowedModules()
	{
		$allAllowedModules = \App\Core\AppConfig::module($this->getName(), 'ALLOW_MODULES');
		foreach ($allAllowedModules as $key => $moduleName) {
			if (!\App\Security\Privilege::isPermitted($moduleName)) {
				unset($allAllowedModules[$key]);
			}
		}
		return $allAllowedModules;
	}
}
