<?php

namespace FreeCRM\Modules\ModTracker\Actions;

/**
 * ChangesReviewedOn Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class ChangesReviewedOn extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$sourceModule = $request->get('sourceModule');
		if (!empty($record)) {
			$recordModel = $this->record ? $this->record : \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($record);
			if (!$recordModel->getModule()->isTrackingEnabled()) {
				throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
		} elseif (!empty($sourceModule)) {
			$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($sourceModule);
			if (!$moduleModel || $moduleModel->isTrackingEnabled()) {
				throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
		} else {
			throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getUnreviewed');
		$this->exposeMethod('reviewChanges');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		$record = $request->get('record');
		$result = \FreeCRM\Modules\ModTracker\Models\Record::setLastReviewed($record);
		\FreeCRM\Modules\ModTracker\Models\Record::unsetReviewed($record, false, $result);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function getUnreviewed(\FreeCRM\Http\Vtiger_Request $request)
	{
		$records = $request->get('recordsId');
		$result = \FreeCRM\Modules\ModTracker\Models\Record::getUnreviewed($records, false, true);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function marks forwarded records as reviewed
	 * @param Vtiger_Request $request
	 */
	public function reviewChanges(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');
		$request->set('module', $sourceModule);
		$result = false;
		$recordsList = Vtiger_Mass_Action::getRecordsListFromRequest($request);
		if (is_array($recordsList) && count($recordsList) > \FreeCRM\AppConfig::module($moduleName, 'REVIEW_CHANGES_LIMIT')) {
			$params = $request->get('selected_ids') === 'all' ? ['viewname', 'selected_ids', 'excluded_ids', 'search_key', 'search_value', 'operator', 'search_params'] : ['selected_ids'];
			foreach ($params as $variable) {
				if ($request->has($variable)) {
					$data[$variable] = $request->get($variable);
				}
			}
			ModTracker_Relation_Model::reviewChangesQueue($data, $sourceModule);
			$cronInfo = \vtlib\Cron::getInstance('LBL_MARK_RECORDS_AS_REVIEWED');
			$message = vtranslate('LBL_REVIEW_CHANGES_LIMIT_DESCRIPTION', $moduleName);
			if ($cronInfo && $cronInfo->getStatus()) {
				$message .= '<br>' . vtranslate('LBL_ESTIMATED_TIME', $moduleName) . ': ' . ($cronInfo->getFrequency() / 60) . vtranslate('LBL_MINUTES');
			}
			$result = [$message];
		} else {
			ModTracker_Relation_Model::reviewChanges($recordsList);
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		return $request->validateWriteAccess();
	}
}
