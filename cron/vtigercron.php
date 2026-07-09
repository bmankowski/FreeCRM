<?php
/* +*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ****************************************************************************** */

// Determine project root directory
$rootDirectory = dirname(__DIR__);
chdir($rootDirectory);

// Bootstrap the cron environment
require_once $rootDirectory . '/vendor/autoload.php';
\App\Modules\Cron\Bootstrap::init();

// Determine execution mode and run cron
if (PHP_SAPI === 'cli') {
	// CLI mode - direct execution
	runCronCLI();
} else {
	// Web mode - authentication required
	runCronWeb();
}

// Write cron execution metadata
\App\Modules\Cron\Bootstrap::writeCronMetadata();

/**
 * Run cron in CLI mode
 * @return void
 */
function runCronCLI(): void
{
	$runner = new \App\Modules\Cron\Runner\CronRunner();
	$serviceName = \App\Modules\Cron\Bootstrap::getServiceNameFromArgs();
	$resetSchedule = \App\Modules\Cron\Bootstrap::hasFlagFromArgs('reset');

	if ($resetSchedule) {
		$runner->resetSchedule($serviceName);
	}

	if (!$resetSchedule || $serviceName !== null) {
		$runner->run($serviceName);
	}
}

/**
 * Run cron in web mode (with authentication check)
 * @return void
 */
function runCronWeb(): void
{
	if (\App\Modules\Cron\Bootstrap::isWebUserAuthenticated()) {
		$runner = new \App\Modules\Cron\Runner\CronRunner();
		$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
		$serviceName = $request->has('service') ? $request->get('service') : null;
		$resetSchedule = $request->has('reset') && ($request->get('reset') === '1' || $request->get('reset') === 'true' || $request->get('reset') === true);

		if ($resetSchedule) {
			$runner->resetSchedule($serviceName);
		}

		if (!$resetSchedule || $serviceName !== null) {
			$runner->run($serviceName);
		}
	} else {
		echo 'Access denied!';
	}
}
