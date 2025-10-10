<?php

namespace FreeCRM\Modules\ServiceContracts\Models;

/**
 * Service contracts module model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Module extends \FreeCRM\Modules\Vtiger\Models\Module
{

	/**
	 * Function to get list view query for popup window
	 * @param \FreeCRM\Modules\Vtiger\Models\ListView $listviewModel
	 * @param \App\QueryGenerator $queryGenerator
	 */
	public function getQueryByRelatedField(\FreeCRM\Modules\Vtiger\Models\ListView $listviewModel, \App\QueryGenerator $queryGenerator)
	{
		if ($listviewModel->get('src_module') == 'HelpDesk' && !$listviewModel->isEmpty('filterFields')) {
			$filterFields = $listviewModel->get('filterFields');
			if (!empty($filterFields['parent_id'])) {
				$queryGenerator->addNativeCondition(['sc_related_to' => $filterFields['parent_id']]);
			}
		}
	}
}
