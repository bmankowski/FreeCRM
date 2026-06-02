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

use App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers;

class AddManualCandidatesAjax extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$userPrivilegesModel->hasModulePermission('ProjektyRekrutacyjne')
			|| !$userPrivilegesModel->hasModulePermission('Kandydaci')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$response = new \App\Http\Vtiger_Response();

		$projectId = $request->getInteger('projectId');
		if ($projectId <= 0) {
			$response->setResult(['success' => false, 'message' => 'PLL_NO_SUCH_RECORD']);
			$response->emit();
			return;
		}

		$candidateIds = $request->get('candidateIds');
		if (\is_string($candidateIds) && $candidateIds !== '') {
			$candidateIds = json_decode($candidateIds, true);
		}
		if (!\is_array($candidateIds)) {
			$raw = $request->getRaw('candidateIds');
			if (\is_string($raw) && $raw !== '') {
				$candidateIds = json_decode($raw, true);
			}
		}
		if (!\is_array($candidateIds) || $candidateIds === []) {
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

		$relationHandler = new GetRelatedMembers();
		$focus = \App\Core\CRMEntity::getInstance('ProjektyRekrutacyjne');
		$added = [];
		$skipped = [];

		foreach ($candidateIds as $rawId) {
			$candidateId = (int) $rawId;
			if ($candidateId <= 0) {
				continue;
			}

			if ($relationHandler->getRelationData($projectId, $candidateId)) {
				$skipped[] = $candidateId;
				continue;
			}

			try {
				\App\Modules\Base\Models\Record::getInstanceById($candidateId, 'Kandydaci');
			} catch (\Exception $e) {
				$skipped[] = $candidateId;
				continue;
			}

			\App\Utils\Utils::relateEntities(
				$focus,
				'ProjektyRekrutacyjne',
				$projectId,
				'Kandydaci',
				$candidateId,
				'getRelatedMembers'
			);
			$added[] = $candidateId;
		}

		$addedCandidates = [];
		foreach ($added as $candidateId) {
			try {
				$candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId, 'Kandydaci');
				$addedCandidates[] = [
					'id' => $candidateId,
					'name' => (string) $candidate->get('name'),
					'detailUrl' => (string) $candidate->getDetailViewUrl(),
					'status' => GetRelatedMembers::STATUS_MANUALLY_ADDED,
				];
			} catch (\Exception $e) {
			}
		}

		$response->setResult([
			'success' => $added !== [] || $skipped !== [],
			'added' => $added,
			'skipped' => $skipped,
			'addedCandidates' => $addedCandidates,
		]);
		$response->emit();
	}
}
