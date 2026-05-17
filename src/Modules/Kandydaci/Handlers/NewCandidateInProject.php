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


namespace App\Modules\Kandydaci\Handlers;

class NewCandidateInProject
{
	public function entityAfterLink(App\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();

		if ($params['sourceModule'] == "Kandydaci" && $params['destinationModule'] == "ProjektyRekrutacyjne") {
			$kandydatId = $params["sourceRecordId"];
			$projektId = $params["destinationRecordId"];
			$this->addComentsAboutNewCandidateInProject($kandydatId, $projektId);         // Wstawienie komentarzy o nowym Kandydacie
			$this->calculateNumberOfCandidatesInProject($projektId);
		} elseif ($params['sourceModule'] == "ProjektyRekrutacyjne" && $params['destinationModule'] == "Kandydaci") {
			$kandydatId = $params["destinationRecordId"];
			$projektId = $params["sourceRecordId"];
			$this->addComentsAboutNewCandidateInProject($kandydatId, $projektId);         // Wstawienie komentarzy o nowym Kandydacie
			$this->calculateNumberOfCandidatesInProject($projektId);
		}
	}

	public function entityAfterUnLink(App\EventHandler $eventHandler)
	{
		$params = $eventHandler->getParams();

		if ($params['sourceModule'] == "Kandydaci" && $params['destinationModule'] == "ProjektyRekrutacyjne") {
			$kandydatId = $params["sourceRecordId"];
			$projektId = $params["destinationRecordId"];
			$this->addComentsAboutRemovingCandidateFromProject($kandydatId, $projektId);         // Wstawienie komentarzy o nowym Kandydacie
			$this->calculateNumberOfCandidatesInProject($projektId);
		} elseif ($params['sourceModule'] == "ProjektyRekrutacyjne" && $params['destinationModule'] == "Kandydaci") {
			$kandydatId = $params["destinationRecordId"];
			$projektId = $params["sourceRecordId"];
			$this->addComentsAboutRemovingCandidateFromProject($kandydatId, $projektId);         // Wstawienie komentarzy o nowym Kandydacie
			$this->calculateNumberOfCandidatesInProject($projektId);
		}
	}

	// Wstawienie komentarzy o nowym Kandydacie
	protected function addComentsAboutRemovingCandidateFromProject($kandydatId, $projektId)
	{
		$recordModelKandydat = \App\Modules\Base\Models\Record::getInstanceById($kandydatId, 'Kandydaci');
		$nazwaKandydata = $recordModelKandydat->get('name');
		$numerKandydata = $recordModelKandydat->get('number');
		$kandydatURL = '/' . $recordModelKandydat->getDetailViewUrl();

		$recordModelProjekt = \App\Modules\Base\Models\Record::getInstanceById($projektId, 'ProjektyRekrutacyjne');
		$nazwaProjektu = $recordModelProjekt->get('nazwa_projektu');
		$numerProjektu = $recordModelProjekt->get('number');
		$projektURL = '/' . $recordModelProjekt->getDetailViewUrl();

		$currentUserId = (int) (\App\User\CurrentUser::getId() ?? 0);
//        Dodanie do Kandydata komentarza o usunięciu Kandydata z konkretnego Projektu
		$commentForCandidate = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
		$commentForCandidate->set('commentcontent', "Kandydat został usunięty z projektu: <a href='$projektURL'>$nazwaProjektu ($numerProjektu)</a>");
		$commentForCandidate->set('related_to', $kandydatId);
		$commentForCandidate->set('assigned_user_id', $currentUserId);
		$commentForCandidate->save();

//        Dodanie do Projektu komentarza o usunięciu Kandydata z tego Projektu
		$commentForProject = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
		$commentForProject->set('commentcontent', "Kandydat <a href='$kandydatURL'>$nazwaKandydata ($numerKandydata)</a> został usunięty z tego projektu.");
		$commentForProject->set('related_to', $projektId);
		$commentForProject->set('assigned_user_id', $currentUserId);
		$commentForProject->save();
	}

	protected function addComentsAboutNewCandidateInProject($candidateId, $projectId)
	{
		$recordModelKandydat = \App\Modules\Base\Models\Record::getInstanceById($candidateId, 'Kandydaci');
		$nazwaKandydata = $recordModelKandydat->get('name');
		$numerKandydata = $recordModelKandydat->get('number');
		$kandydatURL = '/' . $recordModelKandydat->getDetailViewUrl();

		$recordModelProjekt = \App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne');
		$nazwaProjektu = $recordModelProjekt->get('nazwa_projektu');
		$numerProjektu = $recordModelProjekt->get('number');
		$projektURL = '/' . $recordModelProjekt->getDetailViewUrl();

//        Ustawienie w Konsultancie pola, na który projekt została ostatnio wysłana osoba
		$recordModelKandydat->set("projekt_na_ktory_ostatnio_wysl", $projectId);
		$date = date('Y-m-d');
		$recordModelKandydat->set("data_ostatniego_wyslania", $date);
		$recordModelKandydat->save();
		$currentUserId = (int) (\App\User\CurrentUser::getId() ?? 0);

//        Dodanie do Kandydata komentarza o dodaniu Kandydata do konkretnego Projektu
		$commentForCandidate = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
		$commentForCandidate->set('commentcontent', "Kandydat został przypisany do projektu: <a href='$projektURL'>$nazwaProjektu ($numerProjektu)</a>");
		$commentForCandidate->set('related_to', $candidateId);
		$commentForCandidate->set('assigned_user_id', $currentUserId);
		$commentForCandidate->save();

//        Dodanie do Projektu komentarza o dodaniu Kandydata do tego Projektu
		$commentForProject = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
		$commentForProject->set('commentcontent', "Kandydat <a href='$kandydatURL'>$nazwaKandydata ($numerKandydata)</a> został przypisany do tego projektu.");
		$commentForProject->set('related_to', $projectId);
		$commentForProject->set('assigned_user_id', $currentUserId);
		$commentForProject->save();
	}

	public static function calculateNumberOfCandidatesInProject($projectId)
	{
		if (!empty($recordModelProject = \App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne'))) {
			$recordModelProject->calculateNumberOfCandidatesInProject();
			$recordModelProject->save();
		}
	}
}
