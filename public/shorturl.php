<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

// Public directory front controller
// Changes to root directory and includes the main shorturl.php

$rootDir = dirname(__DIR__);

// Fix $_SERVER paths BEFORE changing directory to avoid CSRF validation issues
$_SERVER['SCRIPT_FILENAME'] = str_replace('/public/', '/', $_SERVER['SCRIPT_FILENAME']);
$_SERVER['SCRIPT_NAME'] = str_replace('/public/', '/', $_SERVER['SCRIPT_NAME']);
$_SERVER['PHP_SELF'] = str_replace('/public/', '/', $_SERVER['PHP_SELF']);

chdir($rootDir);

require $rootDir . '/shorturl.php';

