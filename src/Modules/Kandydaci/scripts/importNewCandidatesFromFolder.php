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
\App\Modules\Users\Models\Record::setCurrentUserId(\App\Modules\Users\Models\Record::getUserIdByName('automat'));

$automatId = \App\Modules\Users\Models\Record::getUserIdByName('automat');
$user = \App\Modules\Users\Models\Record::getInstanceById($automatId, 'Users');

\App\Modules\Kandydaci\Crons\TalentDaysPdfImporter::importFromFolder(ROOT_DIRECTORY . '/import/cv/talent_days/');

return;
