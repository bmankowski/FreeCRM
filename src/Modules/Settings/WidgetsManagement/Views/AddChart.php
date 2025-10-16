<?php

namespace FreeCRM\Modules\Settings\WidgetsManagement\Views;



/**
 * Form to add widget
 * @package YetiForce.view
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class AddChart extends \FreeCRM\Modules\Settings\Vtiger\Views\BasicModal
{

	public function getReports()
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$db = \FreeCRM\database\PearDatabase::getInstance();
		$query = 'SELECT reportid, reportname FROM vtiger_report WHERE reporttype = ? AND owner = ?';
		$params = ['chart', $currentUser->getId()];
		$result = $db->pquery($query, $params);
		$recordsReport = [];
		while ($row = $db->getRow($result)) {
			$recordsReport[$row['reportid']] = $row;
		}
		return $recordsReport;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule(false);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $request->getModule());
		$viewer->assign('LIST_REPORTS', $this->getReports());
		$viewer->view('AddChart.tpl', $moduleName);
	}
}
