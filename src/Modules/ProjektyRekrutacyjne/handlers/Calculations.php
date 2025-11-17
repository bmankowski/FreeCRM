<?php

//include_once 'modules/OfertyOrangeFix/D2W_pl.php';
//use D2W_pl;


class ProjektyRekrutacyjne_Calculations_Handler
{

	/**
	 * EntityBeforeSave handler function.
	 *
	 * @param App\EventHandler $eventHandler
	 */
//    public function entityAfterSave(App\EventHandler $eventHandler) {
//        $remuneration = $eventHandler->getRecordModel();
//        //update u_yf_remunerationterms set remuneration_no = concat(ifnull(forma_wspolpracy,' '),' ',round(ifnull(stawka_dla_czlowieka,0),2),'/',ifnull(rodzaj_stawki_dla_czlowieka,'?'));
//
//        $remuneration->set("remuneration_no", $remuneration->get('forma_wspolpracy') . ' ' . round($remuneration->get('stawka_dla_czlowieka'), 2) . '/' . $remuneration->get('rodzaj_stawki_dla_czlowieka'));
//        if ($remuneration->isNew()) {
//
//        }
//    }

	public function entityAfterSave(App\EventHandler $eventHandler)
	{
		$project = $eventHandler->getRecordModel();
//
//        if (empty($consultantId = $project->get("tresc"))) {
//            return;
//        }
	}

	public function entityBeforeSave(App\EventHandler $eventHandler)
	{

		/** @var ProjektyRekrutacyjne_Record_Model $project */
		$project = $eventHandler->getRecordModel();
		$projectId = $project->getId();
		if (empty($projectId)) {
			return;
		}
		$projectName = $project->get("nazwa_projektu");
		//Change all spaces to dash and to lowercase
		$projectName = str_replace(" ", "-", strtolower($projectName));
		$polishChars = array(
			'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
			'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
			'ż' => 'z', 'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E',
			'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'O', 'Ś' => 'S',
			'Ź' => 'Z', 'Ż' => 'Z'
		);
		$projectName = strtr($projectName, $polishChars);

		$links = self::generateJobAdvertisementLinks($projectName, $projectId);
		$project->set("job_advertisement_links", $links);

		$project->calculateNumberOfCandidatesInProject();
	}


	protected static function generateJobAdvertisementLinks($projectName, $projectId): string
	{
		$sources = self::getDataFromSourceOfCandidate();

		// Start building the table
		$links = "<table><tr><th>Source</th><th>Link</th><th>Copy</th></tr>";

		foreach ($sources as $sourceRecord) {
			//Change all backslashes to dash and to lowercase
			$projectName = str_replace("/", "-", strtolower($projectName));
			$link = "https://itconnect.pl/oferta/" . $projectName . "/?action=Aplikuj&projectId=$projectId&sourceId=" . $sourceRecord["zrodlo_aplikacjiid"];
			$links .= "<tr><td>" . $sourceRecord["zrodlo_aplikacji"] . "</td>" .
				"<td><a href='" . $link . "'>" . $link . "</a></td>" .
				"<td><button onclick='navigator.clipboard.writeText(\"$link\")'>&#10697;</button></td>" .
				"</tr>";
		}
		$links .= "</table>";
		return $links;
	}

	public function editViewPreSave(App\EventHandler $eventHandler)
	{
		$response = ['result' => true,];
		return $response;
	}

	protected static function getDataFromSourceOfCandidate()
	{
		$rows = (new App\Db\Query())->select(['vtiger_zrodlo_aplikacji.zrodlo_aplikacjiid', 'vtiger_zrodlo_aplikacji.zrodlo_aplikacji'])->
		from('vtiger_zrodlo_aplikacji')->
		where(['vtiger_zrodlo_aplikacji.generate_link' => 1])->all();
		return $rows;
	}

}

// https://itconnect.pl/oferta/tester-systemow-mikroprocesorowych-na-master-79/
// https://itconnect.pl/oferta/tester-system%C3%B3w-mikroprocesorowych-na-master-79/
