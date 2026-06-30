<?php

/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\HelpDesk\Views;

use App\Http\Vtiger_Request;
use App\Modules\HelpDesk\Models\DetailView;
use App\Modules\HelpDesk\Models\Record;

class TicketWorkflowModal extends \App\Modules\Base\Views\BasicModal
{
	public function checkPermission(Vtiger_Request $request): void
	{
		$recordId = (int) $request->get('record');
		$mode = (string) $request->get('mode');
		if ($recordId <= 0 || !in_array($mode, ['done', 'not_working', 'accept'], true)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$recordModel = Record::getInstanceById($recordId, 'HelpDesk');
		if (!$recordModel->isViewable()) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}

		$allowed = match ($mode) {
			'done' => DetailView::canMarkDone($recordModel),
			'not_working' => DetailView::canReportNotWorking($recordModel),
			'accept' => DetailView::canAccept($recordModel),
			default => false,
		};
		if (!$allowed) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request): void
	{
		$recordId = (int) $request->get('record');
		$mode = (string) $request->get('mode');
		$recordModel = Record::getInstanceById($recordId, 'HelpDesk');

		$viewer = $this->getViewer($request);
		$viewer->assign('MODE', $mode);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_NAME', 'HelpDesk');
		$viewer->assign('SOLUTION', (string) $recordModel->get('solution'));

		$this->preProcess($request);
		$viewer->view('TicketWorkflowModal.tpl', 'HelpDesk');
		$this->postProcess($request);
	}

	public function validateRequest(Vtiger_Request $request): bool
	{
		return $request->validateReadAccess();
	}
}
