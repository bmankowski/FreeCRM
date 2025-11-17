<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

/**
 * Class contacts record model.
 */
class ProjektyRekrutacyjne_Record_Model extends Vtiger_Record_Model
{
    static function generateProjectsFile()
    {
        $projectsDB = ProjektyRekrutacyjne_Record_Model::getActiveProjects();

        foreach ($projectsDB as $key => $project) {
            $id = $project->getId();
            $projects[$id]["id"] = $id;
            $projects[$id]["jezyki"] = "Polski";
			$projects[$id]["title"] = $project->get('nazwa_projektu');
			$projects[$id]["data-utworzenia"] = $project->get('createdtime');


            $projects[$id]["stawka"] = $project->get('our-requirements');
            $projects[$id]["place"] = $project->get('miejsce_pracy');
            $projects[$id]["wybierz-lokalizacje"] = $project->get('workplace_for_map');
            $projects[$id]["stawka"] = $project->get('remuneration');

            $projects[$id]["benefity"] = ProjektyRekrutacyjne_Record_Model::getSerializationForDictionary($project->get('benefits'));
            $projects[$id]["trybpracy"] = ProjektyRekrutacyjne_Record_Model::getSerializationForDictionary($project->get('tryb_pracy'));
            $projects[$id]["forma-zatrudnienia"] = ProjektyRekrutacyjne_Record_Model::getSerializationForDictionary($project->get('form_of_employment'));

            $projects[$id]["wymaganeumiejetnosci"] = ProjektyRekrutacyjne_Record_Model::getCSVForDictionary($project->get('needed_skills'));
            $projects[$id]["milewidziane"] = ProjektyRekrutacyjne_Record_Model::getCSVForDictionary($project->get('nice_to_have_skills'));
            $projects[$id]["specjalizacja"] = ProjektyRekrutacyjne_Record_Model::getSerializationForDictionary($project->get('specialization'));

            $projects[$id]["opisprojektu"] = ProjektyRekrutacyjne_Record_Model::stripAllUnnecessaryTags($project->get('tresc'));
            $projects[$id]["naszewymagania"] =  ProjektyRekrutacyjne_Record_Model::stripAllUnnecessaryTags($project->get('our_requirements'));
            $projects[$id]["twojzakresobowiazkow"] =  ProjektyRekrutacyjne_Record_Model::stripAllUnnecessaryTags($project->get('your_duties'));
			$projects[$id]["tooferujemy"] =  ProjektyRekrutacyjne_Record_Model::stripAllUnnecessaryTags($project->get('we_offer'));
			$projects[$id]["jezyk"] =  ProjektyRekrutacyjne_Record_Model::getSerializationForDictionary($project->get('project_publishing_lang'));
        }
        $projectsJSON = json_encode($projects,JSON_PRETTY_PRINT);

        //Save to file
        file_put_contents('/var/www/export/projects/projects.json', $projectsJSON);
    }

    static function stripAllUnnecessaryTags($string)
    {
        return strip_tags($string, '<p><br><ul><li><ol><strong><em><u><b><i>');
    }


    static function getCSVForDictionary($dictionary)
    {
        if (empty($dictionary)) {
            return null;
        }

        // dictionary have structure of strings diveded by ";"
        // For example "tester;analityk;programista"
        $dictionary = explode(";", $dictionary);
        // Adding to table new string with value "true" after every element of table
        for ($i = 0; $i < count($dictionary); $i++) {
            $dictionary_true[$dictionary[$i]] = "true";
        }
        // Serializing array
        $dictionary = serialize($dictionary_true);
        var_dump($dictionary);
        return $dictionary;
    }

    static function getSerializationForDictionary($dictionary)
    {
        if (empty($dictionary)) {
            return null;
        }
        // dictionary have structure of strings diveded by "|##|"
        // For example "Prywatna opieka medyczna |##| Ubezpieczenie na życie |##| Spotkania integracyjne] "
        $dictionary = explode(" |##| ", $dictionary);
        // Adding to table new string with value "true" after every element of table
        for ($i = 0; $i < count($dictionary); $i++) {
            $dictionary_true[$dictionary[$i]] = "true";
        }
        // Serializing array
        $dictionary = serialize($dictionary_true);

        return $dictionary;
    }

    static function getActiveProjects()
    {

        //      date(sysdate()) to truncate date to days
        $query = "projektyrekrutacyjneid as id
        from u_yf_projektyrekrutacyjne rt inner join vtiger_crmentity e on (e.crmid = rt.projektyrekrutacyjneid)
        where e.deleted=0
        and rt.etap_sprzedazy='Aktywna'";

        $rows = (new App\Db\Query())->select($query)->all();
        //        \App\Log::var_dump($rows["crmid"]);
        if (empty($rows)) {
            return null;
        }
        foreach ($rows as $row) {
            $projects[$row["id"]] = Vtiger_Record_Model::getInstanceById($row["id"], 'ProjektyRekrutacyjne');
        }
        return $projects;
    }
	public function calculateNumberOfCandidatesInProject(){
		$projectId = $this->getId();
		if(empty($projectId)){
			return;
		}

		$query = (new \App\Db\Query())
			->select([
				'PPL_APPLIED' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_APPLIED\' THEN 1 END)',
				'PPL_SENT_TO_CLIENT' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_SENT_TO_CLIENT\' THEN 1 END)',
				'PPL_REJECTED_BY_CLIENT' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_REJECTED_BY_CLIENT\' THEN 1 END)',
				'PPL_OFFER_REJECTED_BY_CANDIDATE' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_OFFER_REJECTED_BY_CANDIDATE\' THEN 1 END)',
				'PPL_ACCEPTED' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_ACCEPTED\' THEN 1 END)',
				'PPL_CANDIDATE_PASSED_SCREENING' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_CANDIDATE_PASSED_SCREENING\' THEN 1 END)',
				'PPL_REJECTED_AFTER_VERIFICATION' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_REJECTED_AFTER_VERIFICATION\' THEN 1 END)',
				'PPL_REJECTED_AFTER_CV' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_REJECTED_AFTER_CV\' THEN 1 END)',
				'PPL_TO_BE_SENT_TO_CLIENT' => 'COUNT(CASE WHEN rel.recruitment_status_rel = \'PPL_TO_BE_SENT_TO_CLIENT\' THEN 1 END)',
				'TOTAL' => 'COUNT(*)'
			])
			->from('u_yf_projekty_rekrutacyjne_relations_members_entity rel')
			->innerJoin('vtiger_crmentity e', 'rel.crmid = e.crmid OR rel.relcrmid = e.crmid')
			->where(['e.deleted' => 0])
			->andWhere(['e.crmid' => $projectId]);

		$result = $query->createCommand()->queryOne();

		$all = $result['TOTAL'];
		$allSent = $result['PPL_SENT_TO_CLIENT'] + $result['PPL_REJECTED_BY_CLIENT'] + $result['PPL_OFFER_REJECTED_BY_CANDIDATE'] + $result['PPL_ACCEPTED'];
		$applied = $result['PPL_APPLIED'];
		$passedScreening = $result['PPL_CANDIDATE_PASSED_SCREENING'];
		$sent = $result['PPL_SENT_TO_CLIENT'];

		$this->set("cvs_applied_number", $applied);
		$this->set("sent_cvs_number", $sent);
		// Złożone/Nowe/Po wstępnej akceptacji/Suma wysłanych/W decyzji klienta



		$statistics = $all . " / ". $applied ." / ". $passedScreening ." / ". $allSent .  " / ". $sent  ;
		$this->set("statistics", $statistics);



	}
	public function getRelatedCandidates() : ?array
	{
		$projectId = $this->getId();
		if(empty($projectId)){
			return null;
		}
		$query1 = (new \App\Db\Query())
			->select(['rel.relcrmid', 'rel.recruitment_status_rel'])
			->from(['rel' => 'u_yf_projekty_rekrutacyjne_relations_members_entity'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'rel.relcrmid = e.crmid')
			->where(['e.deleted' => 0, 'rel.crmid' => $projectId]);

		$query2 = (new \App\Db\Query())
			->select(['rel.crmid', 'rel.recruitment_status_rel'])
			->from(['rel' => 'u_yf_projekty_rekrutacyjne_relations_members_entity'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'rel.crmid = e.crmid')
			->where(['e.deleted' => 0, 'rel.relcrmid' => $projectId]);

		$candidatesIds = (new \App\Db\Query())
			->select('*')
			->from(['u' => $query1->union($query2)])
			->all();


		$candidates = [];
		//Changing ids to objects grouped by status
		foreach ($candidatesIds as $key => $values) {
			$candidate = Vtiger_Record_Model::getInstanceById($values['relcrmid'], 'Kandydaci');
			$status = $values['recruitment_status_rel'];
			$candidates[$status][] = $candidate;
		}
		return $candidates;
	}

}

