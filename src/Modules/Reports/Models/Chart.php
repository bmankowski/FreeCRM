<?php

namespace App\Modules\Reports\Models;


class Chart extends \App\Runtime\BaseModel
{

    /** @var self|null */
    protected $parent;


	public static function getInstanceById($reportModel)
	{
		$self = new self();
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM vtiger_reporttype WHERE reportid = ?', array($reportModel->getId()));
		$data = $db->query_result($result, 0, 'data');
		if (!empty($data)) {
			$decodeData = \App\Json::decode(\App\Utils\ListViewUtils::decodeHtml($data));
			$self->setData($decodeData);
			$self->setParent($reportModel);
			$self->setId($reportModel->getId());
		}
		return $self;
	}

	public function getId()
	{
		return $this->get('reportid');
	}

	public function setId($id)
	{
		$this->set('reportid', $id);
	}

	public function getParent()
	{
		return $this->parent;
	}

	public function setParent($parent)
	{
		$this->parent = $parent;
	}

	public function getChartType()
	{
		$type = $this->get('type');
		if (empty($type))
			$type = 'pieChart';
		return $type;
	}

	public function getGroupByField()
	{
		return $this->get('groupbyfield');
	}

	public function getDataFields()
	{
		return $this->get('datafields');
	}

	public function getData()
	{
		$type = ucfirst($this->getChartType());
		$chartModel = new $type($this);
		return $chartModel->generateData();
	}
}