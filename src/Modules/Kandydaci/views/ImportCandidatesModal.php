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
class Kandydaci_ImportCandidatesModal_View extends \App\Controller\Modal {

    /** {@inheritdoc} */
    public $modalSize = '';

    /** {@inheritdoc} */
    public $showFooter = true;

    /** {@inheritdoc} */
    protected $pageTitle = 'LBL_RUN_IMPORT_CANDIDATES_TITLE';

    /** {@inheritdoc} */
    public $modalIcon = '';

    /** {@inheritdoc} */
    public $successBtn = 'LBL_YES';

    /**
     * @var Kandydaci_Record_Model
     */
    private $recordModel;

    /** {@inheritdoc} */
    public function checkPermission(App\Request $request) {

    }

    /** {@inheritdoc} */
    public function process(App\Request $request) {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('ACTION_NAME', 'ImportCandidatesManually');
        $viewer->assign('RECORD', $request->getInteger('record'));
        $viewer->assign('SELECTED_IDS', $request->getArray('selected_ids', \App\Purifier::INTEGER));
        $viewer->view('Modals/ImportCandidatesModal.tpl', $moduleName);
    }
}
