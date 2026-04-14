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
class ImportCandidatesModal extends \App\Modules\Base\Views\BasicModal {

    /** {@inheritdoc} */
    public $modalSize = '';

    /** {@inheritdoc} */
    public $showFooter = true;

    /** {@inheritdoc} */
    public $pageTitle = 'LBL_RUN_IMPORT_CANDIDATES_TITLE';

    /** {@inheritdoc} */
    public $modalIcon = '';

    /** {@inheritdoc} */
    public $successBtn = 'LBL_YES';

    /**
     * @var \App\Modules\Kandydaci\Models\Record
     */
    private $recordModel;

    /** {@inheritdoc} */
    public function checkPermission(\App\Http\Vtiger_Request $request) {

    }

    /** {@inheritdoc} */
    public function process(\App\Http\Vtiger_Request $request) {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $selectedIds = array_values(array_filter(array_map('intval', $request->getArray('selected_ids')), static fn ($v) => $v > 0));
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('ACTION_NAME', 'ImportCandidatesManually');
        $viewer->assign('RECORD', $request->getInteger('record'));
        $viewer->assign('SELECTED_IDS', $selectedIds);
        $viewer->view('Modals/ImportCandidatesModal.tpl', $moduleName);
    }
}
