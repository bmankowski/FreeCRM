<?php

namespace App\Modules\Settings\CustomView\Views;



/**
 * Sorting View Class for CustomView
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Sorting extends \App\Modules\Settings\Base\Views\BasicModal
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\LangManagement\Models\Module::getInstance($moduleName);
		$sourceModuleId = $request->get('sourceModule');
		$sourceModuleModel = \App\Modules\Base\Models\Module::getInstance($sourceModuleId);
		$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceForModule($sourceModuleModel);
		$recordStructure = $recordStructureInstance->getStructure();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);

		// Added to show event module custom fields
		if ($sourceModuleModel->getName() == 'Calendar') {
			$relatedModuleName = 'Events';
			$relatedModuleModel = \App\Modules\Base\Models\Module::getInstance($relatedModuleName);
			$relatedRecordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceForModule($relatedModuleModel);
			$eventBlocksFields = $relatedRecordStructureInstance->getStructure();
			$viewer->assign('EVENT_RECORD_STRUCTURE_MODEL', $relatedRecordStructureInstance);
			$viewer->assign('EVENT_RECORD_STRUCTURE', $eventBlocksFields);
		}
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('SOURCE_MODULE_MODEL', $sourceModuleModel);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CVID', $request->get('cvid'));
		$this->preProcess($request);
		$viewer->view('Sorting.tpl', $moduleName);
		$this->postProcess($request);
	}
}
