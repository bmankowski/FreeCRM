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

namespace App\Modules\ProjektyRekrutacyjne\Views;

use App\Http\Vtiger_Request;

class KanbanSearchCandidatesModal extends \App\Modules\Base\Views\BasicModal
{
	public function checkPermission(Vtiger_Request $request): void
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$userPrivilegesModel->hasModulePermission('ProjektyRekrutacyjne')
			|| !$userPrivilegesModel->hasModulePermission('Candidates')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$projectId = $request->getInteger('projectId');
		if ($projectId <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		try {
			\App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne');
		} catch (\Exception $e) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request): void
	{
		$projectId = $request->getInteger('projectId');
		$project = \App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne');

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $request->getModule());
		$viewer->assign('PROJECT_ID', $projectId);
		$viewer->assign('CV_BOOLEAN_QUERY', trim((string) $project->get('cv_boolean_query')));

		$this->preProcess($request);
		$viewer->view('Modals/KanbanSearchCandidatesModal.tpl', $request->getModule());
		$this->postProcess($request);
	}

	public function getModalScripts(Vtiger_Request $request): array
	{
		return $this->checkAndConvertJsScripts([
			'modules.ProjektyRekrutacyjne.resources.KanbanCvSkillsQueryStorage',
			'modules.ProjektyRekrutacyjne.resources.KanbanSearchCandidatesModal',
		]);
	}

	public function validateRequest(Vtiger_Request $request): bool
	{
		return $request->validateReadAccess();
	}
}
