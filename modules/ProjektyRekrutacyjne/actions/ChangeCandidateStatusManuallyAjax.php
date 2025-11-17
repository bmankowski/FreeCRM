<?php

use App\Exceptions\IllegalValue;

/**
 * Sen mail manually action model class.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Adrian Koń <a.kon@yetiforce.com>
 */
class ProjektyRekrutacyjne_ChangeCandidateStatusManuallyAjax_Action extends \App\Controller\Action
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
			$sourceStatus = $request->getRaw('sourceStatus');
			$destinationStatus = $request->getRaw('destinationStatus');
		} catch (IllegalValue $e) {
			$response = new Vtiger_Response();
			$response->setResult([
				'success' => false,
				'message' => "PLL_ACCEPTANCE_FAILED"
			]);
			$response->emit();
			App\Log::error("Error ".$e->getDisplayMessage());
			return;
		}
		if(empty($candidateId) || empty($projectId) || empty($sourceStatus) || empty($destinationStatus)){
			$response = new Vtiger_Response();
			$response->setResult([
				'success' => false,
				'message' => "PLL_ACCEPTANCE_FAILED"." candidateId: ".$candidateId." projectId: ".$projectId." sourceStatus: ".$sourceStatus." destinationStatus: ".$destinationStatus
			]);
			$response->emit();
			App\Log::error("PLL_ACCEPTANCE_FAILED"." candidateId: ".$candidateId." projectId: ".$projectId." sourceStatus: ".$sourceStatus." destinationStatus: ".$destinationStatus);
			return;
		}

		try {
			$candidate = Vtiger_Record_Model::getInstanceById($candidateId);
			$project = Vtiger_Record_Model::getInstanceById($projectId);
			/* @var ProjektyRekrutacyjne_GetRelatedMembers_Relation $typeRelationModel */
			$typeRelationModel = Vtiger_Relation_Model::getInstance($project->getModule(), $candidate->getModule())->getTypeRelationModel();
		}catch (Exception $e){
			$response = new Vtiger_Response();
			$response->setResult([
				'success' => false,
				'message' => "PLL_NO_SUCH_RECORD"
			]);
			//@todo add translation
			$response->emit();
			App\Log::error("Error ".$e->getMessage());
			return;
		}

		$result = $typeRelationModel->changeStatus($projectId, $candidateId, $sourceStatus, $destinationStatus);
		$response = new Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => "PLL_ACCEPTANCE_SUCCESS"
		]);
		$response->emit();

	}
}
