<?php

namespace FreeCRM\Modules\Settings\Github\Models;



/**
 * Issue Model
 * @package YetiForce.Github
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Issues
{

	private $valueMap;
	public static $totalCount;

	public function get($key)
	{
		return $this->valueMap->$key;
	}

	static function getInstanceFromArray($issueArray)
	{
		$issueModel = new self();
		$issueModel->valueMap = $issueArray;
		return $issueModel;
	}
}
