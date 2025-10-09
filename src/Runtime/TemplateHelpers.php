<?php
/* +**********************************************************************************
 * The contents of this file are subject to the FreeCRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: FreeCRM Open Source
 * Portions created by FreeCRM are Copyright (C) FreeCRM.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * Global template helper functions
 * These functions are available in the global namespace for Smarty templates
 */

if (!function_exists('vimage_path')) {
	function vimage_path($imageName) {
		$args = func_get_args();
		return call_user_func_array(['FreeCRM\\Runtime\\Vtiger_Theme', 'getImagePath'], $args);
	}
}

if (!function_exists('vimage_path_default')) {
	function vimage_path_default($imageName, $defaultImageName) {
		$args = func_get_args();
		return call_user_func_array(['FreeCRM\\Runtime\\Vtiger_Theme', 'getOrignOrDefaultImgPath'], $args);
	}
}

if (!function_exists('vtemplate_path')) {
	function vtemplate_path($templateName, $moduleName = '') {
		$viewer = FreeCRM\Runtime\FreeCRM_Viewer::getInstance();
		return $viewer->getTemplatePath($templateName, $moduleName);
	}
}
