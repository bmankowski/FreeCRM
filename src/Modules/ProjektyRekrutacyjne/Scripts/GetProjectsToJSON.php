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

\App\Modules\ProjektyRekrutacyjne\Models\Record::generateProjectsFile();
echo "Done";
echo "\n\n\n";

return;


/**
 * Class GetProjectsToJSON
 * Not used at the moment but can be used to send projects file to WWW server
 */
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
