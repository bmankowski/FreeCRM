<?php
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
class Kandydaci_TransformCandidateToConsultantModal_View extends \App\Controller\Modal
{
	/** {@inheritdoc} */
	public $modalSize = '';

	/** {@inheritdoc} */
	public $showFooter = true;

	/** {@inheritdoc} */
	protected $pageTitle = 'LBL_TRANSFORMCANDIDATE_TO_CONSULTANT_TITLE';

	/** {@inheritdoc} */
	public $modalIcon = '';

	/**
	 * @var \Vtiger_Record_Model 
	 */
	private $recordModel; 

	/** {@inheritdoc} */
	public function checkPermission(App\Request $request)
	{
		$this->recordModel = $request->isEmpty('record', true) ? null : Vtiger_Record_Model::getInstanceById($request->getInteger('record'), $request->getModule());
		if (!$this->recordModel || !$this->recordModel->isEditable()) {
			throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
	}
	/** {@inheritdoc} */
	public function process(App\Request $request)
	{
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('ACTION_NAME', 'TransformCandidateToConsultant');
		$viewer->assign('RECORD_ID', $request->getInteger('record'));
		$viewer->assign('POSITION_NAME', "Analityk");
		$viewer->view('Modals/TransformCandidateToConsultantModal.tpl', $moduleName);
	}
}
