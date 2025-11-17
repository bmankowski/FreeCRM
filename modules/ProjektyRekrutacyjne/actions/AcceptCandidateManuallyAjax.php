<?php

use App\Exceptions\IllegalValue;

/**
 * Sen mail manually action model class.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Adrian Koń <a.kon@yetiforce.com>
 */
class ProjektyRekrutacyjne_AcceptCandidateManuallyAjax_Action extends \App\Controller\Action
{
	/**
	 * Process.
	 *
	 * @param \App\Request $request
	 */

	public function checkPermission(App\Request $request)
	{
	}

	public function process(App\Request $request)
	{
		try {
			$candidateId = $request->getInteger('candidateId');
			$projectId = $request->getInteger('projectId');
		} catch (IllegalValue $e) {
			$response = new Vtiger_Response();
			$response->setResult([
				'success' => false,
				'message' => "PLL_ACCEPTANCE_FAILED"
			]);
			$response->emit();
			return;
		}
		$candidate = Vtiger_Record_Model::getInstanceById($candidateId);
		$project = Vtiger_Record_Model::getInstanceById($projectId);
		$typeRelationModel = Vtiger_Relation_Model::getInstance($project->getModule(), $candidate->getModule())->getTypeRelationModel();

		/* @var ProjektyRekrutacyjne_GetRelatedMembers_Relation $typeRelationModel */
		$typeRelationModel = Vtiger_Relation_Model::getInstance($project->getModule(), $candidate->getModule())->getTypeRelationModel();

		$sourceStatus = $typeRelationModel->getRelationData($projectId, $candidateId)['recruitment_status_rel'];
		$result = $typeRelationModel->changeStatus($projectId, $candidateId, $sourceStatus, 'PPL_CANDIDATE_PASSED_SCREENING');

		$response = new Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => "PLL_ACCEPTANCE_SUCCESS"
		]);
		$response->emit();

	}
}
