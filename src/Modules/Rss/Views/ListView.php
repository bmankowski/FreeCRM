<?php

/**
 * RSS ListView View
 *
 * @package   Modules\Rss\Views
 * @author    bmankowski@gmail.com
 * @copyright FreeCRM Public License 1.1
 */

namespace App\Modules\Rss\Views;

/**
 * RSS ListView Class
 */
class ListView extends \App\Modules\Base\Views\ListView
{
	/**
	 * Pre-process the request - setup RSS-specific list view
	 *
	 * @param \App\Http\Vtiger_Request $request Request instance
	 * @param bool                     $display Whether to display
	 *
	 * @return void
	 */
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true): void
	{
		// Call parent's parent (Index::preProcess) to get base functionality
		// Skip ListView::preProcess because RSS doesn't use standard list view model
		\App\Modules\Base\Views\Index::preProcess($request, false);
		
		// Handle RSS-specific setup
		if (!$request->isAjax()) {
			$this->assignSidebarData($request);
			$this->prepareRssListViewData($request);
		}
	}

	/**
	 * Prepare RSS-specific list view data
	 *
	 * @param \App\Http\Vtiger_Request $request Request instance
	 *
	 * @return void
	 */
	protected function prepareRssListViewData(\App\Http\Vtiger_Request $request): void
	{
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);

		// Get RSS record instance
		$recordId = $request->get('id');
		if ($recordId) {
			/** @var \App\Modules\Rss\Models\Record $recordInstance */
			$recordInstance = \App\Modules\Rss\Models\Record::getInstanceById($recordId, $moduleName);
		} else {
			/** @var \App\Modules\Rss\Models\Record $recordInstance */
			$recordInstance = \App\Modules\Rss\Models\Record::getCleanInstance($moduleName);
			$recordInstance->getDefaultRss();
			$recordInstance = \App\Modules\Rss\Models\Record::getInstanceById($recordInstance->getId(), $moduleName);
		}

		// Assign RSS-specific data
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordInstance);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('LISTVIEW_HEADERS', $this->getListViewRssHeaders($moduleName));
		$viewer->assign('VIEW', $request->get('view'));
	}

	/**
	 * Get the list of Script models to be included
	 *
	 * @param \App\Http\Vtiger_Request $request Request instance
	 *
	 * @return array List of script instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		// RSS-specific JavaScript files
		$jsFileNames = [
			'modules.Base.resources.CkEditor'
		];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Get the list view headers for RSS module
	 *
	 * @param string $module Module name
	 *
	 * @return \App\Modules\Base\Models\Field[] List of Field instances
	 */
	protected function getListViewRssHeaders(string $module): array
	{
		$headerFields = [
			'title' => [
				'uitype' => '1',
				'name' => 'title',
				'label' => 'LBL_SUBJECT',
				'typeofdata' => 'V~O',
				'diplaytype' => '1',
			],
			'sender' => [
				'uitype' => '1',
				'name' => 'sender',
				'label' => 'LBL_SENDER',
				'typeofdata' => 'V~O',
				'diplaytype' => '1',
			]
		];

		$fieldModelsList = [];
		foreach ($headerFields as $fieldName => $fieldDetails) {
			$fieldModel = new \App\Modules\Base\Models\Field();
			foreach ($fieldDetails as $name => $value) {
				$fieldModel->set($name, $value);
			}
			$fieldModel->setModule($module);
			$fieldModelsList[$fieldName] = $fieldModel;
		}
		return $fieldModelsList;
	}
}
