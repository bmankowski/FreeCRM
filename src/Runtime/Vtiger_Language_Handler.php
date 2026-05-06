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
			$key = \App\Security\Purifier::decodeHtml($key);
		}

		$translatedString = self::getLanguageTranslatedString($currentLanguage, $key, $module);

		// label not found in users language pack, then check in the default language pack(config.inc.php)
		if ($translatedString === null) {
			$defaultLanguage = \App\Core\AppConfig::main('default_language');
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
			\App\Log\Log::warning('Invalid module name - module: ' . var_export($module, true));
			return null;
		}

		if (is_numeric($module)) {
			// ok, we have a tab id, lets turn it into name
			$module = \App\Utils\ModuleUtils::getModuleName($module);
			// If getModuleName returned false (module doesn't exist), use default 'Vtiger'
			if ($module === false || empty($module)) {
				$module = 'Vtiger';
			} elseif (strpos($module, '.') === false) {
				// If module name doesn't contain dot and file doesn't exist, try Settings submodule
				$qualifiedName = 'languages.' . $language . '.' . $module;
				$file = \App\Core\Loader::resolveNameToPath($qualifiedName);
				if (!file_exists($file)) {
					// Try Settings submodule path
					$settingsQualifiedName = 'languages.' . $language . '.Settings.' . $module;
					$settingsFile = \App\Core\Loader::resolveNameToPath($settingsQualifiedName);
					if (file_exists($settingsFile)) {
						$module = 'Settings.' . $module;
					}
				}
			}
		} else {
			// Ensure module is not empty or false
			if (empty($module) || $module === false) {
				$module = 'Vtiger';
			} else {
				$module = str_replace(':', '.', $module);
			}
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

		\App\Log\Log::warning(sprintf("cannot translate this: '%s' for module '%s' (or base or Vtiger), lang: %s", $key, $module, $language));
		return $key;
	}

	/**
	 * Function that returns translation strings from file
	 * Loads language files in JSON format (YetiForce compatible)
	 * 
	 * @param string $language Language code (e.g., 'pl_pl', 'en_us')
	 * @param string $module Module name (e.g., 'Vtiger', 'Accounts')
	 * @return array Array with 'languageStrings' and 'jsLanguageStrings' keys
	 */
	public static function getModuleStringsFromFile(string $language, $module = 'Vtiger')
	{
		$module = str_replace(':', '.', $module);
		if (!isset(self::$languageContainer[$language][$module])) {
			$qualifiedName = 'languages.' . $language . '.' . $module;
			$file = \App\Core\Loader::resolveNameToPath($qualifiedName);
			$languageStrings = [];
			$jsLanguageStrings = [];
			
			if (file_exists($file)) {
				$jsonContent = file_get_contents($file);
				$data = json_decode($jsonContent, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
					$languageStrings = isset($data['languageStrings']) && is_array($data['languageStrings']) ? $data['languageStrings'] : [];
					$jsLanguageStrings = isset($data['jsLanguageStrings']) && is_array($data['jsLanguageStrings']) ? $data['jsLanguageStrings'] : [];
				} else {
					\App\Log\Log::error(sprintf('Invalid JSON in language file: %s (error: %s)', $file, json_last_error_msg()));
				}
			} else {
				// Only log warning if module is not empty (empty module warnings are expected for common strings)
				if (!empty($module)) {
					\App\Log\Log::warning(sprintf('Language file does not exist, module: %s ,language: %s', $module, $language));
				}
			}

			self::$languageContainer[$language][$module]['languageStrings'] = $languageStrings;
			self::$languageContainer[$language][$module]['jsLanguageStrings'] = $jsLanguageStrings;
			
			// Load custom language files if enabled
			if (\App\Core\AppConfig::performance('LOAD_CUSTOM_FILES')) {
				$qualifiedName = 'custom.languages.' . $language . '.' . $module;
				$file = \App\Core\Loader::resolveNameToPath($qualifiedName);
				if (file_exists($file)) {
					$jsonContent = file_get_contents($file);
					$data = json_decode($jsonContent, true);
					if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
						$customLanguageStrings = isset($data['languageStrings']) && is_array($data['languageStrings']) ? $data['languageStrings'] : [];
						$customJsLanguageStrings = isset($data['jsLanguageStrings']) && is_array($data['jsLanguageStrings']) ? $data['jsLanguageStrings'] : [];
						
						// Merge custom strings (custom overrides standard)
						foreach ($customLanguageStrings as $key => $val) {
							self::$languageContainer[$language][$module]['languageStrings'][$key] = $val;
						}
						foreach ($customJsLanguageStrings as $key => $val) {
							self::$languageContainer[$language][$module]['jsLanguageStrings'][$key] = $val;
						}
					} else {
						\App\Log\Log::error(sprintf('Invalid JSON in custom language file: %s (error: %s)', $file, json_last_error_msg()));
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

		$translatedLanguage = \App\Http\Vtiger_Session::get('translated_language');
		if ($translatedLanguage) {
			$language = $translatedLanguage;
	} elseif (\App\Http\Vtiger_Session::get('language') != '') {
		$language = \App\Http\Vtiger_Session::get('language');
	} else {
		$language = \App\Modules\Users\Models\Record::getCurrentUserModel()->get('language');
	}

		$language = empty($language) ? \App\Core\AppConfig::main('default_language') : strtolower($language);
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
		$value = \App\Core\AppConfig::main('default_language');
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
		$db = \App\Database\PearDatabase::getInstance();
		$language_query = 'SELECT prefix, label FROM vtiger_language WHERE active = 1';
		$result = $db->pquery($language_query, []);
		$num_rows = $db->num_rows($result);
		$languages = [];
		for ($i = 0; $i < $num_rows; $i++) {
			$lang_prefix = \App\Utils\ListViewUtils::decodeHtml($db->query_result($result, $i, 'prefix'));
			$label = \App\Utils\ListViewUtils::decodeHtml($db->query_result($result, $i, 'label'));
			$languages[$lang_prefix] = $label;
		}
		asort($languages);
		return $languages;
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
	public static function translate(?string $key, ...$args)
	{
		if (empty($key)) {
			return 'NO KEY';
		}
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
