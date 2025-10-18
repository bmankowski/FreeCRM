<?php

namespace App\Modules\Settings\WebserviceApps\Models;



/**
 * Record Model
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Record extends \App\Modules\Settings\Vtiger\Models\Record
{

	public function getId()
	{
		return $this->get('id');
	}

	public function getName()
	{
		return $this->get('name');
	}

	public static function getInstanceById($recordId)
	{
		if (empty($recordId)) {
			return false;
		}
		$model = new self();
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM w_yf_servers WHERE id = ? LIMIT 1', [$recordId]);
		$data = $db->getRow($result);
		$model->setData($data);
		return $model;
	}
}
