<?php

namespace App\Modules\ModTracker\Actions;

/**
 * ChangesReviewedOn Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class ChangesReviewedOn extends \App\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$sourceModule = $request->get('sourceModule');
		if (!empty($record)) {
			$recordModel = $this->record ? $this->record : \App\Modules\Vtiger\Models\Record::getInstanceById($record);
			if (!$recordModel->getModule()->isTrackingEnabled()) {
				throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
		} elseif (!empty($sourceModule)) {
			$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($sourceModule);
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

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		$record = $request->get('record');
		$result = \App\Modules\ModTracker\Models\Record::setLastReviewed($record);
		\App\Modules\ModTracker\Models\Record::unsetReviewed($record, false, $result);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function getUnreviewed(\App\Http\Vtiger_Request $request)
	{
		$records = $request->get('recordsId');
		$result = \App\Modules\ModTracker\Models\Record::getUnreviewed($records, false, true);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function marks forwarded records as reviewed
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function reviewChanges(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$sourceModule = $request->get('sourceModule');
		$request->set('module', $sourceModule);
		$result = false;
		$recordsList = \App\Modules\Vtiger\Actions\Mass::getRecordsListFromRequest($request);
		if (is_array($recordsList) && count($recordsList) > \App\AppConfig::module($moduleName, 'REVIEW_CHANGES_LIMIT')) {
			$params = $request->get('selected_ids') === 'all' ? ['viewname', 'selected_ids', 'excluded_ids', 'search_key', 'search_value', 'operator', 'search_params'] : ['selected_ids'];
			foreach ($params as $variable) {
				if ($request->has($variable)) {
					$data[$variable] = $request->get($variable);
				}
			}
			\App\Modules\ModTracker\Models\Relation::reviewChangesQueue($data, $sourceModule);
			$cronInfo = \vtlib\Cron::getInstance('LBL_MARK_RECORDS_AS_REVIEWED');
			$message = \App\Runtime\Vtiger_Language_Handler::translate('LBL_REVIEW_CHANGES_LIMIT_DESCRIPTION', $moduleName);
			if ($cronInfo && $cronInfo->getStatus()) {
				$message .= '<br>' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_ESTIMATED_TIME', $moduleName) . ': ' . ($cronInfo->getFrequency() / 60) . \App\Runtime\Vtiger_Language_Handler::translate('LBL_MINUTES');
			}
			$result = [$message];
		} else {
			\App\Modules\ModTracker\Models\Relation::reviewChanges($recordsList);
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		return $request->validateWriteAccess();
	}
}
