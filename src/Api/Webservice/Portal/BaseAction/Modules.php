<?php
namespace App\Api\Portal\BaseAction;

/**
 * Get modules list action class
 * @package YetiForce.WebserviceAction
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Modules extends \App\Api\Core\BaseAction
{

	/** @var string[] Allowed request methods */
	public $allowedMethod = ['GET'];

	/**
	 * Get modules list
	 * @return string[]
	 */
	public function get()
	{
		return \App\Api\Core\Module::getPermittedModules();
	}
}
