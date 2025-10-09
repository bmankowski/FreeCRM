<?php

namespace FreeCRM\Modules\Vtiger\Dashboards;

/**
 * ServicesSoldToRenew Dashboard Class
 * @package YetiForce.Dashboard
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class ServicesSoldToRenew extends Dashboard
{

	public function setData($data)
	{
		if (empty($data['orderby'])) {
			$data['orderby'] = 'dateinservice';
			$data['sortorder'] = 'asc';
		}
		return $this->data = $data;
	}

	public function getTargetModule()
	{
		return 'OSSSoldServices';
	}

	public function getTargetFields()
	{
		return ['id', 'productname', 'parent_id', 'dateinservice'];
	}

	public function getFieldNameToSecondButton()
	{
		return 'osssoldservices_renew';
	}

	public function getConditions()
	{
		return ['ssservicesstatus' => 'PLL_ACCEPTED', 'osssoldservices_renew' => 'PLL_WAITING_FOR_RENEWAL'];
	}
}
