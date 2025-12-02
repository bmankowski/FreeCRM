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
class TransformDocumentToCVModal extends \App\Controller\Modal
{
	/** {@inheritdoc} */
	public $modalSize = '';

	/** {@inheritdoc} */
	public $showFooter = true;

	/** {@inheritdoc} */
	protected $pageTitle = 'LBL_TRANSFORM_DOCUMENT_TO_CV_TITLE';

	/** {@inheritdoc} */
	public $modalIcon = '';

	/**
	 * @var \App\Modules\Base\Models\Record 
	 */
	private $recordModel; 

	/** {@inheritdoc} */
	public function checkPermission(App\Request $request)
	{
		$this->recordModel = $request->isEmpty('candidateId', true) ? null : \App\Modules\Base\Models\Record::getInstanceById($request->getInteger('candidateId'), $request->getModule());
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
		$viewer->assign('ACTION_NAME', 'TransformDocumentToCV');
		$viewer->assign('CANDIDATE_ID', $request->getInteger('candidateId'));
		$viewer->assign('DOCUMENT_ID', $request->getInteger('documentId'));
		$viewer->view('Modals/TransformDocumentToCVModal.tpl', $moduleName);
	}
}
