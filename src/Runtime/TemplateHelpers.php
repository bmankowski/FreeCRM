<?php
/* +**********************************************************************************
 * The contents of this file are subject to the App Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: App Open Source
 * Portions created by App are Copyright (C) App.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * Global template helper functions
 * These functions are available in the global namespace for Smarty templates
 */

if (!function_exists('vimage_path')) {
	function vimage_path($imageName) {
		$args = func_get_args();
		return call_user_func_array(['App\\Runtime\\Vtiger_Theme', 'getThemeImageWebUrl'], $args);
	}
}

if (!function_exists('vimage_path_default')) {
	function vimage_path_default($imageName, $defaultImageName) {
		$args = func_get_args();
		return call_user_func_array(['App\\Runtime\\Vtiger_Theme', 'getOrignOrDefaultImgPath'], $args);
	}
}

if (!function_exists('vtemplate_path')) {
	function vtemplate_path($templateName, $moduleName = '') {
		$viewer = App\Runtime\CRM_Viewer::getInstance();
		return $viewer->getTemplatePath($templateName, $moduleName);
	}
}

if (!function_exists('script_time')) {
	/**
	 * @param array<string, mixed> $params
	 */
	function script_time(array $params, \Smarty_Internal_Template $template): string
	{
		$time = isset($GLOBALS['startTime']) ? round(microtime(true) - $GLOBALS['startTime'], 3) : 0;
		if (!empty($params['assign'])) {
			$template->assign($params['assign'], $time);
			return '';
		}

		return (string) $time;
	}
}

if (!function_exists('vresource_url')) {
	function vresource_url($url) {
		if (!is_string($url) || $url === '') {
			return '';
		}
		if (stripos($url, '://') !== false) {
			return $url;
		}
		if (!defined('ROOT_DIRECTORY')) {
			return $url;
		}
		$path = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($url, '/');
		if (is_file($path)) {
			return $url . '?s=' . filemtime($path);
		}
		return $url;
	}
}
