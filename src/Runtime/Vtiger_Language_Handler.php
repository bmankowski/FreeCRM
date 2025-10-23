<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * Class to handler language translations
 */

namespace App\Runtime;

use App\Http\App\Http\Vtiger_Session;
use App\Purifier;
use App\Log;
use App\Module;

class Vtiger_Language_Handler
{

	//Contains module language translations
	protected static $languageContainer;

	/**
	 * Functions that gets translated string
	 * @param string $key - string which need to be translated
	 * @param string $module - module scope in which the translation need to be check
	 * @return string - translated string
	 */
	public static function getTranslatedString($key, $module = 'Vtiger', $currentLanguage = false)
	{
		if ($currentLanguage === false) {
			$currentLanguage = self::getLanguage();
		}

		//decoding for Start Date & Time and End Date & Time 
		if (!is_array($key)) {
			$key = Purifier::decodeHtml($key);
		}

		$translatedString = self::getLanguageTranslatedString($currentLanguage, $key, $module);

		// label not found in users language pack, then check in the default language pack(config.inc.php)
		if ($translatedString === null) {
			$defaultLanguage = vglobal('default_language');
			if (!empty($defaultLanguage) && strcasecmp($defaultLanguage, $currentLanguage) !== 0) {
				$translatedString = self::getLanguageTranslatedString($defaultLanguage, $key, $module);
			}
		}

		// If translation is not found then return label
		if ($translatedString === null) {
			return $key;
		}

		return $translatedString;
	}

	/**
	 * Function returns language specific translated string
	 * @param string $language - en_us etc
	 * @param string $key - label
	 * @param string $module - module name
	 * @return string translated string or null if translation not found
	 */
	public static function getLanguageTranslatedString($language, $key, $module = 'Vtiger')
	{
		if ($key === '') { // nothing to translate
			return '';
		}

		if (is_array($module)) {
			Log::warning('Invalid module name - module: ' . var_export($module, true));
			return null;
		}

		if (is_numeric($module)) {
			// ok, we have a tab id, lets turn it into name
			$module = Module::getModuleName($module);
		} else {
			$module = str_replace(':', '.', $module);
		}

		$moduleStrings = self::getModuleStringsFromFile($language, $module);
		if (!empty($moduleStrings['languageStrings'][$key])) {
			return stripslashes($moduleStrings['languageStrings'][$key]);
		}

		// Lookup for the translation in base module, in case of sub modules, before ending up with common strings
		if (strpos($module, '.') > 0) {
			$baseModule = substr($module, 0, strpos($module, '.'));
			if ($baseModule === 'Settings') {
				$baseModule = 'Settings.Vtiger';
			}

			$moduleStrings = self::getModuleStringsFromFile($language, $baseModule);
			if (!empty($moduleStrings['languageStrings'][$key])) {
				return stripslashes($moduleStrings['languageStrings'][$key]);
			}
		}

		$commonStrings = self::getModuleStringsFromFile($language);
		if (!empty($commonStrings['languageStrings'][$key])) {
			return stripslashes($commonStrings['languageStrings'][$key]);
		}

		return null;
	}

	/**
	 * Functions that gets translated string for Client side
	 * @param string $key - string which need to be translated
	 * @param string $module - module scope in which the translation need to be check
	 * @return string - translated string
	 */
	public static function getJSTranslatedString($language, $key, $module = 'Vtiger')
	{
		$module = str_replace(':', '.', $module);
		$moduleStrings = self::getModuleStringsFromFile($language, $module);
		if (!empty($moduleStrings['jsLanguageStrings'][$key])) {
			return $moduleStrings['jsLanguageStrings'][$key];
		}

		// Lookup for the translation in base module, in case of sub modules, before ending up with common strings
		if (strpos($module, '.') > 0) {
			$baseModule = substr($module, 0, strpos($module, '.'));
			if ($baseModule === 'Settings') {
				$baseModule = 'Settings.Vtiger';
			}

			$moduleStrings = self::getModuleStringsFromFile($language, $baseModule);
			if (!empty($moduleStrings['jsLanguageStrings'][$key])) {
				return $moduleStrings['jsLanguageStrings'][$key];
			}
		}

		$commonStrings = self::getModuleStringsFromFile($language);
		if (!empty($commonStrings['jsLanguageStrings'][$key])) {
			return $commonStrings['jsLanguageStrings'][$key];
		}

		Log::warning(sprintf("cannot translate this: '%s' for module '%s' (or base or Vtiger), lang: %s", $key, $module, $language));
		return $key;
	}

	/**
	 * Function that returns translation strings from file
	 * @global <array> $languageStrings - language specific string which is used in translations
	 * @param string $module - module Name
	 * @return <array> - array if module has language strings else returns empty array
	 */
	public static function getModuleStringsFromFile(string $language, $module = 'Vtiger')
	{
		$module = str_replace(':', '.', $module);
		if (!isset(self::$languageContainer[$language][$module])) {
			$qualifiedName = 'languages.' . $language . '.' . $module;
			$file = \App\Loader::resolveNameToPath($qualifiedName);
			$languageStrings = [];
			$jsLanguageStrings = [];
			if (file_exists($file)) {
				require $file;
			} else {
				Log::warning(sprintf('Language file does not exist, module: %s ,language: %s', $module, $language));
			}

			self::$languageContainer[$language][$module]['languageStrings'] = $languageStrings;
			self::$languageContainer[$language][$module]['jsLanguageStrings'] = $jsLanguageStrings;
			if (\App\AppConfig::performance('LOAD_CUSTOM_FILES')) {
				$qualifiedName = 'custom.languages.' . $language . '.' . $module;
				$file = \App\Loader::resolveNameToPath($qualifiedName);
				if (file_exists($file)) {
					require $file;
					foreach ($languageStrings as $key => $val) {
						self::$languageContainer[$language][$module]['languageStrings'][$key] = $val;
					}

					foreach ($jsLanguageStrings as $key => $val) {
						self::$languageContainer[$language][$module]['jsLanguageStrings'][$key] = $val;
					}
				}
			}
		}

		if (isset(self::$languageContainer[$language][$module])) {
			return self::$languageContainer[$language][$module];
		}

		return [];
	}

	public static $language = false;

	/**
	 * Function that returns current language
	 * @return string -
	 */
	public static function getLanguage()
	{
		if (static::$language) {
			return static::$language;
		}

		if (vglobal('translated_language')) {
			$language = vglobal('translated_language');
	} elseif (\App\Http\Vtiger_Session::get('language') != '') {
		$language = \App\Http\Vtiger_Session::get('language');
	} else {
		$language = \App\Modules\Users\Models\Record::getCurrentUserModel()->get('language');
	}

		$language = empty($language) ? vglobal('default_language') : strtolower($language);
		static::$language = $language;
		return $language;
	}

	/**
	 * Function that returns current language short name
	 * @return string -
	 */
	public static function getShortLanguageName()
	{
		$language = self::getLanguage();
		return substr($language, 0, 2);
	}

	/**
	 * Function returns module strings
	 * @param string $module - module Name
	 * @param string languageStrings or jsLanguageStrings
	 * @return <Array>
	 */
	public static function export($module, $type = 'languageStrings')
	{
		$userSelectedLanguage = self::getLanguage();
		$value = vglobal('default_language');
		$languages = [$userSelectedLanguage];
		//To merge base language and user selected language translations
		if ($userSelectedLanguage != $value) {
			$languages[] = $value;
		}


		$resultantLanguageString = [];
		foreach ($languages as $language) {
			$exportLangString = [];

			$moduleStrings = self::getModuleStringsFromFile($language, $module);
			if (!empty($moduleStrings[$type])) {
				$exportLangString = $moduleStrings[$type];
			}

			// Lookup for the translation in base module, in case of sub modules, before ending up with common strings
			if (strpos($module, '.') > 0) {
				$baseModule = substr($module, 0, strpos($module, '.'));
				if ($baseModule === 'Settings') {
					$baseModule = 'Settings.Vtiger';
				}

				$moduleStrings = self::getModuleStringsFromFile($language, $baseModule);
				if (!empty($moduleStrings[$type])) {
					$exportLangString += $moduleStrings[$type];
				}
			}

			$commonStrings = self::getModuleStringsFromFile($language);
			if (!empty($commonStrings[$type])) {
				$exportLangString += $commonStrings[$type];
			}

			$resultantLanguageString += $exportLangString;
		}

		return $resultantLanguageString;
		;
	}

	/**
	 * Function to returns all language information
	 * @return <Array>
	 */
	public static function getAllLanguages()
	{
		return \vtlib\LanguageExport::getAll();
	}

	/**
	 * Function to get the label name of the Langauge package
	 * @param string $name
	 */
	public static function getLanguageLabel($name)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$languageResult = $db->pquery('SELECT label FROM vtiger_language WHERE prefix = ?', [$name]);
		if ($db->num_rows($languageResult)) {
			return $db->query_result($languageResult, 0, 'label');
		}

		return false;
	}

	public static function getTranslateSingularModuleName(string $moduleName)
	{
		return Vtiger_Language_Handler::getTranslatedString('SINGLE_' . $moduleName, $moduleName);
	}
	public static function translate(string $key, ...$args)
	{
		// Use the existing Vtiger translation system
		try {
			// First argument after key is module name, rest are sprintf parameters
			$moduleName = $args[0] ?? 'Vtiger';
			$sprintfArgs = array_slice($args, 1);

			$formattedString = Vtiger_Language_Handler::getTranslatedString($key, $moduleName);

			// If there are sprintf parameters, format the string
			if (!empty($sprintfArgs)) {
				return call_user_func_array('vsprintf', [$formattedString, $sprintfArgs]);
			}

			return $formattedString;
		} catch (\Exception $exception) {
			// Fallback to original key if translation fails
			return "ERROR:" . $key;
		}
	}
	
}
