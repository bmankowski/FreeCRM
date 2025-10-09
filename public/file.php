<?php
/**
 * Basic file to handle files
 * @package YetiForce.Files
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

// Public directory front controller
// Changes to root directory and includes the main file.php

$rootDir = dirname(__DIR__);

// Fix $_SERVER paths BEFORE changing directory to avoid CSRF validation issues
$_SERVER['SCRIPT_FILENAME'] = str_replace('/public/', '/', $_SERVER['SCRIPT_FILENAME']);
$_SERVER['SCRIPT_NAME'] = str_replace('/public/', '/', $_SERVER['SCRIPT_NAME']);
$_SERVER['PHP_SELF'] = str_replace('/public/', '/', $_SERVER['PHP_SELF']);

chdir($rootDir);

require $rootDir . '/file.php';

