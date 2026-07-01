<?php

namespace App\Modules\ProjektyRekrutacyjne\Handlers;

/* +***********************************************************************************
 * ProjektyRekrutacyjne Calculations Handler.
 *
 * @copyright FreeCRM
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 * *********************************************************************************** */

class Calculations
{

    /**
     * EntityAfterSave handler function.
     *
     * @param \App\Events\EventHandler $eventHandler
     */
    public function entityAfterSave(\App\Events\EventHandler $eventHandler)
    {
        $project = $eventHandler->getRecordModel();
//
//        if (empty($consultantId = $project->get("tresc"))) {
//            return;
//        }
    }

    /**
     * EntityBeforeSave handler function.
     *
     * @param \App\Events\EventHandler $eventHandler
     */
    public function entityBeforeSave(\App\Events\EventHandler $eventHandler)
    {

        /** @var \App\Modules\ProjektyRekrutacyjne\Models\Record $project */
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
        $projectName = str_replace("/", "-", strtolower($projectName));

        $links = "<div class=\"job-ad-links\">";
        foreach ($sources as $sourceRecord) {
            $link = "https://itconnect.pl/oferta/" . $projectName . "/?action=Aplikuj&projectId=$projectId&sourceId=" . $sourceRecord["application_sourceid"];
            $source = htmlspecialchars((string) $sourceRecord["application_source"], ENT_QUOTES, 'UTF-8');
            $href = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $links .= "<a class=\"job-ad-link-copy\" href=\"" . $href . "\" target=\"_blank\">" .
                "<span class=\"glyphicon glyphicon-copy\" aria-hidden=\"true\"></span>" .
                "<span class=\"job-ad-link-copy__source\">" . $source . "</span>" .
                "</a>";
        }
        $links .= "</div>";
        return $links;
    }

    public function editViewPreSave(\App\Events\EventHandler $eventHandler)
    {
        $response = ['result' => true,];
        return $response;
    }

    protected static function getDataFromSourceOfCandidate()
    {
        $rows = (new \App\Db\Query())
            ->select(['z.application_sourceid', 'z.application_source'])
            ->from(['z' => 'vtiger_application_source'])
            ->where(['z.generate_link' => 1])
            ->all();
        return $rows;
    }
}

// https://itconnect.pl/oferta/tester-systemow-mikroprocesorowych-na-master-79/
// https://itconnect.pl/oferta/tester-system%C3%B3w-mikroprocesorowych-na-master-79/
