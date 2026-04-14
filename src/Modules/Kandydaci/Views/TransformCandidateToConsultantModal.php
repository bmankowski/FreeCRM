<?php

namespace App\Modules\Kandydaci\Views;

/**
 * Create outsource offers modal view file.
 *
 * @package   View
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Arkadiusz Sołek <a.solek@yetiforce.com>
 */
/**
 * Create outsource offers modal view class.
 */
class TransformCandidateToConsultantModal extends \App\Modules\Base\Views\BasicModal
{
	/** {@inheritdoc} */
	public $modalSize = '';

	/** {@inheritdoc} */
	public $showFooter = true;

	/** {@inheritdoc} */
	public $pageTitle = 'LBL_TRANSFORMCANDIDATE_TO_CONSULTANT_TITLE';

	/** {@inheritdoc} */
	public $modalIcon = '';

	/**
	 * @var \App\Modules\Base\Models\Record 
	 */
	private $recordModel; 

	/** {@inheritdoc} */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$this->recordModel = $request->isEmpty('record') ? null : \App\Modules\Base\Models\Record::getInstanceById($request->getInteger('record'), $request->getModule());
		if (!$this->recordModel || !$this->recordModel->isEditable()) {
			throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
	}
	/** {@inheritdoc} */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$this->preProcess($request);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('ACTION_NAME', 'TransformCandidateToConsultant');
		$viewer->assign('RECORD_ID', $request->getInteger('record'));
		$viewer->assign('POSITION_NAME', "Analityk");
		$viewer->view('Modals/TransformCandidateToConsultantModal.tpl', $moduleName);
		$this->postProcess($request);
	}
}
