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




echo "Test\n\n";
echo "Done";
echo "\n\n\n";

return;


\App\Process::$requestMode = 'Cron';
\App\Utils\ConfReport::$sapi = 'cron';
\App\Session::init();
\App\User::setCurrentUserId(\App\User::getUserIdByName("automat"));
error_reporting(E_ERROR);
//\App\Log::warning("Odpalony CRON dla Zamówień");

//askoczek@itconnect.pl


$automatId = \App\User::getUserIdByName("automat");
$user = \App\User::getUserModel($automatId);

//$consultantId=1275883;
//
//$consultant = Vtiger_Record_Model::getInstanceById($consultantId, 'Konsultanci');
//
//testCheckAllConsultantsData();


//             \App\Mailer::sendFromTemplate(["template" =>1382229,
//                 'to' => ["akozlowska@itconnect.pl" => "Agnieszka Kozłowska"]
//				]); 
// 
//             \App\Mailer::sendFromTemplate(["template" =>1382229,
//                 'to' => ["bmankowski@itconnect.pl" => "Bartłomiej Mańkowski"]
//				]);


$mailStatus = \App\Mailer::addMail([
    'to' => ["bmankowski@itconnect.pl" => "Bartolome"],
    'from' => ["testy@itconnect.com.pl" => "Tester"],
    'subject' => "Test załącznika z cache 3",
    'content' => "Test - wersja maila z Szanownymi Panami.",
]);


//$mailStatus = \App\Mailer::addMail([	'to' => ["bmankowski@itconnect.pl" => "Bartolome"],
//					'subject' => "Test załącznika z cache",
//					'content' => "Test - wersja maila z Szanownymi Panami.",
//					'attachments' => ['/var/www/yetiforce/cache/fileToSend2.txt','Załącznik testowy'],
//				]);
//
//$mailStatus = \App\Mailer::addMail([	'to' => ["gskoczek@itconnect.pl" => "Griegorij Saltador"],
//					'from' => ['email' => "yeti@itconnect.pl", 'name' => "Prince of ZIMBABWE"],
//					'subject' => "Test udanego weekendu z from",
//					'content' => "Test wysyłania załączników ze skryptów z portalu YetiForce + życzenia udanego weekendu :)",
//					'attachments' => ['ids' => ['1285095']],
//				]);


class GetProjectsToJSON
{
    public static function sendProjectFileToWWW()
    {
        try {
            $connection = ssh2_connect('itconnect.pl', 22222);
            if (!$connection) {
                throw new \Exception('Connection failed');
            }

            $auth = ssh2_auth_password($connection, 'itconnect@itconnect.pl', 'p7hgBTAn3');
            if (!$auth) {
                throw new \Exception('Authentication failed');
            }

            // Specify the local file path and the destination path on the remote server
            $localFilePath = '/var/www/export/projects/projects.json';
            $remoteFilePath = 'public_html/autoinstalator/wordpress/import/projects/projects.json';

            // Copying file to server
            if (!ssh2_scp_send($connection, $localFilePath, $remoteFilePath)) {
                throw new \Exception('Failed to copy file to remote server');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
