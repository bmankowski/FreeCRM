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

namespace App\Modules\ProjektyRekrutacyjne\Actions;

use App\Modules\Candidates\Exceptions\InvalidCvSkillsExpressionException;
use App\Modules\ProjektyRekrutacyjne\Services\KanbanCandidatePicker;

class KanbanPickCandidatesAjax extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$userPrivilegesModel->hasModulePermission('ProjektyRekrutacyjne')
			|| !$userPrivilegesModel->hasModulePermission('Candidates')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$response = new \App\Http\Vtiger_Response();
		$projectId = $request->getInteger('projectId');
		$cvSkills = trim((string) $request->get('cv_skills'));

		if ($projectId <= 0 || $cvSkills === '') {
			$response->setResult(['success' => false, 'message' => 'PLL_NO_SUCH_RECORD']);
			$response->emit();
			return;
		}

		try {
			\App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne');
		} catch (\Exception $e) {
			$response->setResult(['success' => false, 'message' => 'PLL_NO_SUCH_RECORD']);
			$response->emit();
			return;
		}

		if ($request->get('mode') === 'ids') {
			try {
				$candidateIds = KanbanCandidatePicker::listAllCandidateIds($projectId, $cvSkills);
			} catch (InvalidCvSkillsExpressionException $e) {
				$response->setResult([
					'success' => false,
					'invalidExpression' => true,
					'message' => $e->getMessageKey(),
					'messageDetail' => $e->getDetail(),
				]);
				$response->emit();
				return;
			}

			$response->setResult([
				'success' => true,
				'candidateIds' => $candidateIds,
				'totalCount' => \count($candidateIds),
			]);
			$response->emit();
			return;
		}

		$pageNumber = $request->getInteger('page');
		if ($pageNumber <= 0) {
			$pageNumber = 1;
		}

		try {
			$listViewModel = KanbanCandidatePicker::createListViewModel($projectId, $cvSkills);
		} catch (InvalidCvSkillsExpressionException $e) {
			$response->setResult([
				'success' => false,
				'invalidExpression' => true,
				'message' => $e->getMessageKey(),
				'messageDetail' => $e->getDetail(),
			]);
			$response->emit();
			return;
		}

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('page', (string) $pageNumber);

		$entries = $listViewModel->getListViewEntries($pagingModel);
		$totalCount = $listViewModel->getListViewCount();
		$pageLimit = $pagingModel->getPageLimit();
		$pageCount = max(1, (int) ceil($totalCount / max(1, $pageLimit)));

		$candidateIds = [];
		foreach ($entries as $entry) {
			$candidateIds[] = (int) $entry->getId();
		}
		$projectNames = KanbanCandidatePicker::projectNamesByCandidateIds($candidateIds);

		$viewer = new \App\Runtime\CRM_Viewer();
		$viewer->assign('MODULE_NAME', 'ProjektyRekrutacyjne');
		$viewer->assign('ENTRIES', $entries);
		$viewer->assign('PROJECT_NAMES', $projectNames);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('LISTVIEW_COUNT', $totalCount);
		$viewer->assign('ENTRIES_COUNT', \count($entries));

		$html = $viewer->view('KanbanPickCandidatesList.tpl', 'ProjektyRekrutacyjne', true);

		$response->setResult([
			'success' => true,
			'html' => $html,
			'pageNumber' => $pageNumber,
			'pageCount' => $pageCount,
			'totalCount' => $totalCount,
			'entriesCount' => \count($entries),
		]);
		$response->emit();
	}
}
