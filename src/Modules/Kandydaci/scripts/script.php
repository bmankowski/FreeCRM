<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */
/**
 * Description of importFromFile
 *
 * @author bmankowski
 */
if (file_exists('include/main/WebUI.php')) {
    include_once 'include/main/WebUI.php';
} else {
    chdir(__DIR__ . '/../');
    if (file_exists('include/main/WebUI.php')) {
        include_once 'include/main/WebUI.php';
    } else {
        chdir(__DIR__ . '/../../');
        if (file_exists('include/main/WebUI.php')) {
            include_once 'include/main/WebUI.php';
        }
        chdir(__DIR__ . '/../../../');
        if (file_exists('include/main/WebUI.php')) {
            include_once 'include/main/WebUI.php';
        }
    }
}




// Include ModTracker
require_once('modules/ModTracker/ModTracker.php');

\App\Process::$requestMode = 'Cron';
\App\Utils\ConfReport::$sapi = 'cron';
\App\Session::init();
\App\User::setCurrentUserId(\App\User::getUserIdByName("automat"));

//\App\Log::warning("Odpalony CRON dla Zamówień");


$automatId = \App\User::getUserIdByName("automat");
$user = \App\User::getUserModel($automatId);
// var_dump(phpversion());
// echo "\n\n\n";

echo PHP_VERSION_ID;
echo "\n\n\n";
// return;

$allProjects = Utils::getAllProjektyRekrutacyjne();
foreach ($allProjects as $projectId) {
    \App\Modules\Kandydaci\Handlers\NewCandidateInProject::calculateNumberOfCandidatesInProject($projectId);
}
return;  
//$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
//try {
//    $phoneUtil->isValidNumber($phoneUtil->parse($value));
//    echo "Błędny";
//} catch (\libphonenumber\NumberParseException $e) {
//    Kandydaci_ScheduledImport_Cron::vecho("Nieprawidłowy numer u kandydata\n");
//    echo "Poprawny";
//}
//Kandydaci_ScheduledImport_Cron::test();
//$str = '[{"name":"cv2-v.jpg","size":681750,"path":"storage\/MultiImage\/Kandydaci\/cv_img_file\/2023\/November\/week2\/2058dfac112a8002e98f29998e5429480b860382bYxzHDhD18","key":"2058dfac112a8002e98f29998e5429480b860382bYxzHDhD18","type":"image\/jpeg"}]';
//
//$value = \App\Json::decode($str);
////var_dump($value);
//foreach ($value as $item) {
////    var_dump($item);
//    $file = \App\Fields\File::loadFromInfo([
//                'path' => ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $item['path'],
//                'name' => $item['name'],
//    ]);
//    if (file_exists($file->getPath())) {
////        echo ("xxx");
//        header('Pragma: cache');
//        header('Cache-control: max-age=86400, public');
//        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
//        header('Content-type: ' . $file->getMimeType());
//        header('Content-transfer-encoding: binary');
//        header('Content-length: ' . $file->getSize());
//        header('Content-disposition: inline; filename="' . $item['name'] . '"');
//        readfile($file->getPath());
//    }
//}


$rows = Utils::getDataToTransformCandidateDocumentsToCV();

foreach ($rows as $key => $value) {
    echo "Processing: ".$value["candidateid"] . " " . $value["attachmentsid"] . " " . $value["path"] . " " . $value["name"] . " " . $value["type"] . "\n";
    \App\Modules\Kandydaci\Models\Record::transformFileToCV($value["candidateid"], $value["attachmentsid"], $value["name"],"/var/www/yetiforce/".$value["path"],$value["type"] );
}

echo "Done";
echo "\n\n\n";

class Utils {

    static function getDataToTransformCandidateDocumentsToCV() {

        $query = (new App\Db\Query())->select(["candidateid", "attachmentsid", "path", "name", "type"])->from("bmn_documents_for_cv2");
        
        $rows = $query->all();
        
//        1325000
//        where e.crmid between (1320000 and 1325000)
        if (empty($rows)) {
            return null;
        }

        return $rows;
    }
    

    public static function calculateNumberOfCandidatesInProject($projectId) {
        if (!empty($recordModelProject = \App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne'))) {
//        $count1 = (new \App\Db\Query())->from('vtiger_crmentityrel')->
//                where(['crmid' => $projectId])->
//                andWhere(['module' => 'ProjektyRekrutacyjne'])->
//                andWhere(['relmodule' => 'Kandydaci'])->
//                count();
//        $count2 = (new \App\Db\Query())->from('vtiger_crmentityrel')->
//                where(['relcrmid' => $projectId])->
//                andWhere(['module' => 'Kandydaci'])->
//                andWhere(['relmodule' => 'ProjektyRekrutacyjne'])->
//                count();
//
//            $count1 = (new \App\Db\Query())->select('rel.crmid,rel.relcrmid')->from('u_yf_projekty_rekrutacyjne_relations_members_entity rel')->
//                    innerJoin('vtiger_crmentity e','rel.relcrmid=e.crmid')->where('e.deleted=0')->
//                    andWhere(['or', ['rel.crmid' => $projectId], ['rel.relcrmid' => $projectId]])->
//                    distinct()->
//                    count();

            $count1 = (new \App\Db\Query())->select('rel.crmid,rel.relcrmid')->from('u_yf_projekty_rekrutacyjne_relations_members_entity rel')->
                    innerJoin('vtiger_crmentity e', 'rel.relcrmid=e.crmid')->where('e.deleted=0')->
                    andWhere(['rel.crmid' => $projectId])->
                    distinct()->
                    count();

            $count2 = (new \App\Db\Query())->select('rel.crmid,rel.relcrmid')->from('u_yf_projekty_rekrutacyjne_relations_members_entity rel')->
                    innerJoin('vtiger_crmentity e', 'rel.crmid=e.crmid')->where('e.deleted=0')->
                    andWhere(['rel.relcrmid' => $projectId])->
                    distinct()->
                    count();

            $recordModelProject->set("sent_cvs_number", $count1 + $count2);
            $recordModelProject->save();
        }
    }

    static function getAllProjektyRekrutacyjne() {
        $rows = (new App\Db\Query())->select('u_yf_projektyrekrutacyjne.projektyrekrutacyjneid')->from('u_yf_projektyrekrutacyjne')
                ->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_projektyrekrutacyjne.projektyrekrutacyjneid')
                ->where(['vtiger_crmentity.deleted' => 0])
                ->all();
        if (empty($rows)) {
            return null;
        }
        foreach ($rows as $key => $value) {
            $newRows[$key] = $value["projektyrekrutacyjneid"];
        }
        return $newRows;
    }
}
