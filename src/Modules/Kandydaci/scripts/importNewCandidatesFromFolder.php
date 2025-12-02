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

$automatId = \App\User::getUserIdByName("automat");
$user = \App\User::getUserModel($automatId);

\App\Modules\Kandydaci\Crons\ScheduledImport::importAllCandidatesFromFolder("/var/www/import/cv/talent_days/");

return;
