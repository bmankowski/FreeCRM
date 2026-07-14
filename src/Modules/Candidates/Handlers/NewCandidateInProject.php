<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of RefreshDocsNumber
 *
 * @author bmankowski
 */
/*
  $data = [
  'CRMEntity' => $this->getParentModuleModel()->getEntityInstance(),
  'sourceModule' => $sourceModuleName,
  'sourceRecordId' => $sourceRecordId,
  'destinationModule' => $this->getRelationModuleModel()->getName(),
  'relationId' => $this->getId(),
  ];

 * $data['destinationRecordId'] = $destinationRecordId;

 * $eventHandler->setParams($data);
 *

 */


namespace App\Modules\Candidates\Handlers;

class NewCandidateInProject
{
	public function entityAfterLink(\App\Events\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();

		if ($params['sourceModule'] == "Candidates" && $params['destinationModule'] == "ProjektyRekrutacyjne") {
			$this->onCandidateLinkedToProject((int) $params['sourceRecordId'], (int) $params['destinationRecordId']);
		} elseif ($params['sourceModule'] == "ProjektyRekrutacyjne" && $params['destinationModule'] == "Candidates") {
			$this->onCandidateLinkedToProject((int) $params['destinationRecordId'], (int) $params['sourceRecordId']);
		}
	}

	public function entityAfterUnLink(\App\Events\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();

		if ($params['sourceModule'] == "Candidates" && $params['destinationModule'] == "ProjektyRekrutacyjne") {
			$candidateId = $params["sourceRecordId"];
			$projektId = $params["destinationRecordId"];
			$this->addComentsAboutRemovingCandidateFromProject($candidateId, $projektId);
			$this->calculateNumberOfCandidatesInProject($projektId);
		} elseif ($params['sourceModule'] == "ProjektyRekrutacyjne" && $params['destinationModule'] == "Candidates") {
			$candidateId = $params["destinationRecordId"];
			$projektId = $params["sourceRecordId"];
			$this->addComentsAboutRemovingCandidateFromProject($candidateId, $projektId);
			$this->calculateNumberOfCandidatesInProject($projektId);
		}
	}

	protected function addComentsAboutRemovingCandidateFromProject($candidateId, $projektId)
	{
		$candidateRecord = \App\Modules\Base\Models\Record::getInstanceById($candidateId, 'Candidates');
		$candidateName = $candidateRecord->get('name');
		$candidateNumber = $candidateRecord->get('number');
		$candidateUrl = '/' . $candidateRecord->getDetailViewUrl();

		$recordModelProjekt = \App\Modules\Base\Models\Record::getInstanceById($projektId, 'ProjektyRekrutacyjne');
		$nazwaProjektu = $recordModelProjekt->get('nazwa_projektu');
		$numerProjektu = $recordModelProjekt->get('number');
		$projektURL = '/' . $recordModelProjekt->getDetailViewUrl();

		$currentUserId = (int) (\App\User\CurrentUser::getId() ?? 0);
		$commentForCandidate = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
		$commentForCandidate->set('commentcontent', "Kandydat został usunięty z projektu: <a href='$projektURL'>$nazwaProjektu ($numerProjektu)</a>");
		$commentForCandidate->set('related_to', $candidateId);
		$commentForCandidate->set('assigned_user_id', $currentUserId);
		$commentForCandidate->save();

		$commentForProject = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
		$commentForProject->set('commentcontent', "Kandydat <a href='$candidateUrl'>$candidateName ($candidateNumber)</a> został usunięty z tego projektu.");
		$commentForProject->set('related_to', $projektId);
		$commentForProject->set('assigned_user_id', $currentUserId);
		$commentForProject->save();
	}

	public function onCandidateLinkedToProject(int $candidateId, int $projectId): void
	{
		$this->addComentsAboutNewCandidateInProject($candidateId, $projectId);
		$this->calculateNumberOfCandidatesInProject($projectId);
	}

	protected function addComentsAboutNewCandidateInProject(int $candidateId, int $projectId): void
	{
		$candidateRecord = \App\Modules\Base\Models\Record::getInstanceById($candidateId, 'Candidates');
		$candidateName = $candidateRecord->get('name');
		$candidateNumber = $candidateRecord->get('number');
		$candidateUrl = '/' . $candidateRecord->getDetailViewUrl();

		$recordModelProjekt = \App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne');
		$nazwaProjektu = $recordModelProjekt->get('nazwa_projektu');
		$numerProjektu = $recordModelProjekt->get('number');
		$projektURL = '/' . $recordModelProjekt->getDetailViewUrl();

		$candidateRecord->set("last_sent_to_project_id", $projectId);
		$date = date('Y-m-d');
		$candidateRecord->set("last_sent_to_project_date", $date);
		$candidateRecord->save();
		$currentUserId = (int) (\App\User\CurrentUser::getId() ?? 0);

		$relationHandler = new \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers();
		$relationRow = $relationHandler->getRelationData($projectId, $candidateId);
		$status = $relationRow['recruitment_status_rel'] ?? '';

		if ($status === \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers::STATUS_APPLIED) {
			$candidateComment = "Kandydat zaaplikował do projektu: <a href='$projektURL'>$nazwaProjektu ($numerProjektu)</a>";
			$projectComment = "Kandydat <a href='$candidateUrl'>$candidateName ($candidateNumber)</a> zaaplikował do tego projektu.";
		} elseif ($status === \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers::STATUS_AI_ADDED) {
			$candidateComment = "Kandydat został dodany przez AI do projektu: <a href='$projektURL'>$nazwaProjektu ($numerProjektu)</a>";
			$projectComment = "Kandydat <a href='$candidateUrl'>$candidateName ($candidateNumber)</a> został dodany przez AI do tego projektu.";
		} else {
			$candidateComment = "Kandydat został dodany ręcznie do projektu: <a href='$projektURL'>$nazwaProjektu ($numerProjektu)</a>";
			$projectComment = "Kandydat <a href='$candidateUrl'>$candidateName ($candidateNumber)</a> został dodany ręcznie do tego projektu.";
		}

		$commentForCandidate = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
		$commentForCandidate->set('commentcontent', $candidateComment);
		$commentForCandidate->set('related_to', $candidateId);
		$commentForCandidate->set('assigned_user_id', $currentUserId);
		$commentForCandidate->save();

		$commentForProject = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
		$commentForProject->set('commentcontent', $projectComment);
		$commentForProject->set('related_to', $projectId);
		$commentForProject->set('assigned_user_id', $currentUserId);
		$commentForProject->save();
	}

	public static function calculateNumberOfCandidatesInProject($projectId)
	{
		if (!empty($recordModelProject = \App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne'))) {
			/** @var \App\Modules\ProjektyRekrutacyjne\Models\Record $recordModelProject */
			$recordModelProject->calculateNumberOfCandidatesInProject();
			$recordModelProject->save();
		}
	}
}
