<?php

namespace FreeCRM\Modules\Settings\WebserviceApps\Models;



/**
 * 
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Module extends \FreeCRM\Modules\Settings\Vtiger\Models\Module
{

	/**
	 * Webservice apps types
	 * @return string[]
	 */
	static public function getTypes()
	{
		return ['Portal'];
	}

	static public function getServers()
	{

		$db = \App\Db::getInstance('webservice');
		$query = new \App\Db\Query();
		$query->from('w_#__servers');
		return $query->createCommand($db)->queryAllByGroup(true);
	}

	static public function getActiveServers($type = '')
	{
		$db = \App\Db::getInstance('webservice');
		$query = new \App\Db\Query();
		$query->from('w_#__servers')->andWhere(['status' => 1]);
		if (!empty($type)) {
			$query->andWhere(['type' => $type]);
		}
		return $query->createCommand($db)->queryAllByGroup(1);
	}
}
