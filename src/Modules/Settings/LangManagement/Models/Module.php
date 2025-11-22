<?php

namespace App\Modules\Settings\LangManagement\Models;



/**
 * LangManagement Module Class
 * @package YetiForce.Settings.Model
 * @license licenses/License.html
 * @author YetiForce.com
 */
class Module extends \App\Modules\Settings\Base\Models\Module
{

	const url_separator = '^';

	public function getLang($data = false)
	{
		$query = (new \App\Db\Query())->from('vtiger_language');
		if ($data && $data['prefix'] != '') {
			$query->where(['prefix' => $data['prefix']]);
		}
		$output = [];
		$dataReader = $query->createCommand()->query();
		while ($row = $dataReader->read()) {
			$output[$row['prefix']] = $row;
		}
		return $output;
	}

	/**
	 * Remove translation
	 * @param array $params
	 * @return (string|bool)[]
	 */
	public static function deleteTranslation($params)
	{
		$change = false;
		$langkey = $params['langkey'];
		foreach ($params['lang'] as $lang) {
			$edit = false;
			$mod = str_replace(self::url_separator, '.', $params['mod']);
			if (\App\Core\AppConfig::performance('LOAD_CUSTOM_FILES')) {
				$qualifiedName = "custom.languages.$lang.$mod";
			} else {
				$qualifiedName = "languages.$lang.$mod";
			}
			$fileName = \App\Core\Loader::resolveNameToPath($qualifiedName);
			if (file_exists($fileName)) {
				$fileContent = file($fileName);
				foreach ($fileContent as $key => $file_row) {
					if (self::parse_data("'$langkey'", $file_row)) {
						unset($fileContent[$key]);
						$edit = $change = true;
					}
				}
				if ($edit) {
					$fileContent = implode("", $fileContent);
					$filePointer = fopen($fileName, 'w+');
					fwrite($filePointer, $fileContent);
					fclose($filePointer);
				}
			}
		}
		return $change ? ['success' => true, 'data' => 'LBL_DeleteTranslationOK'] : ['success' => false, 'data' => 'LBL_DELETE_TRANSLATION_FAILED'];
	}

	/**
	 * Save
	 * @param array $params
	 * @return array
	 */
	public static function saveTranslation($params)
	{
		if ($params['is_new'] == 'true') {
			$result = self::addTranslation($params);
		} else {
			$result = self::updateTranslation($params);
		}
		return $result;
	}

	/**
	 * Add translation
	 * Saves translations to JSON format
	 * @param array $params
	 * @return (string|bool)[]
	 */
	public static function addTranslation($params)
	{
		$lang = $params['lang'];
		$mod = $params['mod'];
		$langkey = $params['langkey'];
		$val = $params['val'];
		$mod = str_replace(self::url_separator, '.', $mod);

		if (\App\Core\AppConfig::performance('LOAD_CUSTOM_FILES')) {
			$qualifiedName = "custom.languages.$lang.$mod";
		} else {
			$qualifiedName = "languages.$lang.$mod";
		}
		$fileName = \App\Core\Loader::resolveNameToPath($qualifiedName);
		$fileExists = file_exists($fileName);
		
		// Load existing JSON data or initialize empty structure
		$data = [
			'languageStrings' => [],
			'jsLanguageStrings' => []
		];
		
		if ($fileExists) {
			$jsonContent = file_get_contents($fileName);
			$decoded = json_decode($jsonContent, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				$data['languageStrings'] = isset($decoded['languageStrings']) && is_array($decoded['languageStrings']) ? $decoded['languageStrings'] : [];
				$data['jsLanguageStrings'] = isset($decoded['jsLanguageStrings']) && is_array($decoded['jsLanguageStrings']) ? $decoded['jsLanguageStrings'] : [];
			}
		} else {
			if (\App\Core\AppConfig::performance('LOAD_CUSTOM_FILES')) {
				self::createCustomLangDirectory($params);
			}
		}
		
		// Determine which array to modify
		$targetArray = ($params['type'] === 'php') ? 'languageStrings' : 'jsLanguageStrings';
		
		// Check if key already exists
		if (isset($data[$targetArray][$langkey])) {
			return ['success' => false, 'data' => 'LBL_KeyExists'];
		}
		
		// Add new translation
		$data[$targetArray][$langkey] = $val;
		
		// Encode to JSON
		$jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		
		if ($jsonContent === false) {
			return ['success' => false, 'data' => 'LBL_ERROR_ENCODING_JSON'];
		}
		
		// Write JSON file
		if (file_put_contents($fileName, $jsonContent) === false) {
			return ['success' => false, 'data' => 'LBL_ERROR_WRITING_FILE'];
		}
		
		return ['success' => true, 'data' => 'LBL_AddTranslationOK'];
	}

	/**
	 * Function to update translation
	 * Updates translations in JSON format
	 * @param array $params
	 * @return (string|bool)[]
	 */
	public static function updateTranslation($params)
	{
		$lang = $params['lang'];
		$mod = $params['mod'];
		$langkey = $params['langkey'];
		$val = $params['val'];
		$mod = str_replace(self::url_separator, '.', $mod);
		$customType = \App\Core\AppConfig::performance('LOAD_CUSTOM_FILES');
		
		if ($customType) {
			$qualifiedName = "custom.languages.$lang.$mod";
		} else {
			$qualifiedName = "languages.$lang.$mod";
		}
		$fileName = \App\Core\Loader::resolveNameToPath($qualifiedName);
		$fileExists = file_exists($fileName);
		
		// Load existing JSON data
		$data = [
			'languageStrings' => [],
			'jsLanguageStrings' => []
		];
		
		if ($fileExists) {
			$jsonContent = file_get_contents($fileName);
			$decoded = json_decode($jsonContent, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				$data['languageStrings'] = isset($decoded['languageStrings']) && is_array($decoded['languageStrings']) ? $decoded['languageStrings'] : [];
				$data['jsLanguageStrings'] = isset($decoded['jsLanguageStrings']) && is_array($decoded['jsLanguageStrings']) ? $decoded['jsLanguageStrings'] : [];
			}
		} else {
			if ($customType) {
				self::createCustomLangDirectory($params);
				// If custom file doesn't exist, add translation instead
				return self::addTranslation($params);
			}
			return ['success' => false, 'data' => 'LBL_DO_NOT_POSSIBLE_TO_MAKE_CHANGES'];
		}
		
		// Determine which array to modify
		$targetArray = ($params['type'] === 'php') ? 'languageStrings' : 'jsLanguageStrings';
		
		// Check if key exists
		if (!isset($data[$targetArray][$langkey])) {
			if ($customType) {
				return self::addTranslation($params);
			}
			return ['success' => false, 'data' => 'LBL_DO_NOT_POSSIBLE_TO_MAKE_CHANGES'];
		}
		
		// Update translation
		$data[$targetArray][$langkey] = $val;
		
		// Encode to JSON
		$jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		
		if ($jsonContent === false) {
			return ['success' => false, 'data' => 'LBL_ERROR_ENCODING_JSON'];
		}
		
		// Write JSON file
		if (file_put_contents($fileName, $jsonContent) === false) {
			return ['success' => false, 'data' => 'LBL_ERROR_WRITING_FILE'];
		}
		
		return ['success' => true, 'data' => 'LBL_UpdateTranslationOK'];
	}

	/**
	 * Function creates directory structure
	 * @param array $params
	 * @throws \App\Exceptions\AppException
	 */
	public static function createCustomLangDirectory($params)
	{
		$mod = explode(self::url_separator, $params['mod']);
		$folders = ['custom', 'languages', $params['lang']];
		if (count($mod) > 1) {
			$folders[] = 'Settings';
		}
		foreach ($folders as $key => $name) {
			$loc .= DIRECTORY_SEPARATOR . $name;
			if (!file_exists(ROOT_DIRECTORY . $loc)) {
				if (!mkdir(ROOT_DIRECTORY . $loc)) {
					\App\Log\Log::warning("No permissions to create directories: $loc");
					throw new \App\Exceptions\AppException('No permissions to create directories');
				}
			}
		}
	}

	/**
	 * Function gets translations
	 * @param string[] $lang
	 * @param string $mod
	 * @param type $ShowDifferences
	 * @return type
	 */
	public function loadLangTranslation($lang, $mod, $ShowDifferences = 0)
	{
		$keysPhp = $keysJs = $langs = $langTab = $respPhp = $respJs = [];
		$mod = str_replace(self::url_separator, '/', $mod);
		if (self::parse_data(',', $lang)) {
			$langs = explode(',', $lang);
		} else {
			$langs[] = $lang;
		}
		foreach ($langs as $lang) {
			$langData = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile($lang, $mod);
			if ($langData) {
				$langTab[$lang]['php'] = $langData['languageStrings'];
				$langTab[$lang]['js'] = $langData['jsLanguageStrings'];
				$keysPhp = array_merge($keysPhp, array_keys($langData['languageStrings']));
				$keysJs = array_merge($keysJs, array_keys($langData['jsLanguageStrings']));
			}
		}
		$keysPhp = array_unique($keysPhp);
		$keysJs = array_unique($keysJs);
		foreach ($keysPhp as $key) {
			foreach ($langs as $language) {
				$respPhp[$key][$language] = htmlentities($langTab[$language]['php'][$key], ENT_QUOTES, 'UTF-8');
			}
		}
		foreach ($keysJs as $key) {
			foreach ($langs as $language) {
				$respJs[$key][$language] = htmlentities($langTab[$language]['js'][$key], ENT_QUOTES, 'UTF-8');
			}
		}
		return ['php' => $respPhp, 'js' => $respJs, 'langs' => $langs, 'keys' => $keys];
	}

	public function loadAllFieldsFromModule($lang, $mod, $showDifferences = 0)
	{
		$variablesFromFile = $this->loadLangTranslation($lang, 'HelpInfo', $showDifferences);
		$output = [];
		if (self::parse_data(',', $lang)) {
			$langs = explode(",", $lang);
		} else {
			$langs[] = $lang;
		}
		$output['langs'] = $langs;
		$dataReader = (new \App\Db\Query())
				->from('vtiger_field')
				->where(['tabid' => \App\Utils\ModuleUtils::getModuleId($mod), 'presence' => [0, 2]])
				->createCommand()->query();
		while ($row = $dataReader->read()) {
			$output['php'][$mod . '|' . $row['fieldlabel']]['label'] = \App\Runtime\Vtiger_Language_Handler::translate($row['fieldlabel'], $mod);
			$output['php'][$mod . '|' . $row['fieldlabel']]['info'] = array('view' => explode(',', $row['helpinfo']), 'fieldid' => $row['fieldid']);
			foreach ($langs AS $lang) {
				$output['php'][$mod . '|' . $row['fieldlabel']][$lang] = stripslashes($variablesFromFile['php'][$mod . '|' . $row['fieldlabel']][$lang]);
			}
		}
		return $output;
	}

	public function getModFromLang($lang)
	{
		if ($lang == '' || $lang === null) {
			$lang = 'en_us';
		} else {
			if (self::parse_data(',', $lang)) {
				$langA = explode(",", $lang);
				$lang = $langA[0];
			}
		}
		$dir = "languages/$lang";
		if (!file_exists($dir)) {
			return false;
		}
		$files = array();
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
		foreach ($objects as $name => $object) {
			if (strpos($object->getFilename(), '.php') !== false) {
				$name = str_replace('.php', "", $name);
				$val = str_replace($dir . DIRECTORY_SEPARATOR, "", $name);
				$key = str_replace($dir . DIRECTORY_SEPARATOR, "", $name);
				$key = str_replace("/", self::url_separator, $key);
				$key = str_replace("\\", self::url_separator, $key);
				$val = str_replace(DIRECTORY_SEPARATOR, "|", $val);
				$files[$key] = $val;
			}
		}
		return self::SettingsTranslate($files);
	}

	public function SettingsTranslate($langs)
	{
		$settings = array();
		foreach ($langs as $key => $lang) {
			if (self::parse_data('|', $lang)) {
				$langArray = explode("|", $lang);
				unset($langs[$key]);
				$settings[$key] = \App\Runtime\Vtiger_Language_Handler::translate($langArray[1], 'Settings:' . $langArray[1]);
			} else {
				$langs[$key] = \App\Runtime\Vtiger_Language_Handler::translate($key, $key);
			}
		}
		return array('mods' => $langs, 'settings' => $settings);
	}

	public function add($params)
	{
		if (self::getLang($params)) {
			return ['success' => false, 'data' => 'LBL_LangExist'];
		}
		self::CopyDir('languages/en_us/', 'languages/' . $params['prefix'] . '/');
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->insert('vtiger_language', [
			'id' => $db->getUniqueId('vtiger_language'),
			'name' => $params['name'],
			'prefix' => $params['prefix'],
			'label' => $params['label'],
		])->execute();
		return ['success' => true, 'data' => 'LBL_AddDataOK'];
	}

	public function save($params = null)
	{
		if ($params && $params['type'] == 'Checkbox') {
			$val = $params['val'] == 'true' ? 1 : 0;
			\App\Db\Db::getInstance()->createCommand()
				->update('vtiger_language', [$params['name'] => $val], ['prefix' => $params['prefix']])
				->execute();
			return true;
		}
		return false;
	}

	public function saveView($params)
	{
		if (!is_array($params['value'])) {
			$params['value'] = [$params['value']];
		}
		$value = implode(',', $params['value']);
		\App\Db\Db::getInstance()->createCommand()
			->update('vtiger_field', ['helpinfo' => $value], ['fieldid' => $params['fieldid']])
			->execute();
		return array('success' => true, 'data' => 'LBL_SUCCESSFULLY_UPDATED');
	}

	public static function deleteLanguage($params)
	{
		$dir = 'languages/' . $params['prefix'];
		if (file_exists($dir)) {
			self::DeleteDir($dir);
		}
		\App\Db\Db::getInstance()->createCommand()
			->delete('vtiger_language', ['prefix' => $params['prefix']])
			->execute();
		return true;
	}

	/**
	 * Parse data
	 * @param string $a
	 * @param string $b
	 * @return boolean
	 */
	public static function parse_data($a, $b)
	{
		$resp = false;
		if ($b != '' && stristr($b, $a) !== false) {
			$resp = true;
		}
		return $resp;
	}

	public function DeleteDir($dir)
	{
		$fd = opendir($dir);
		if (!$fd)
			return false;
		while (($file = readdir($fd)) !== false) {
			if ($file == "." || $file == "..")
				continue;
			if (is_dir($dir . "/" . $file)) {
				self::DeleteDir($dir . "/" . $file);
			} else {
				unlink("$dir/$file");
			}
		}
		closedir($fd);
		rmdir($dir);
	}

	public function CopyDir($src, $dst)
	{
		$dir = opendir($src);
		@mkdir($dst);
		while (false !== ( $file = readdir($dir))) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if (is_dir($src . '/' . $file)) {
					self::CopyDir($src . '/' . $file, $dst . '/' . $file);
				} else {
					copy($src . '/' . $file, $dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	public function setAsDefault($lang)
	{

		\App\Log\Log::trace("Entering \App\Modules\Settings\LangManagement\Models\Module::setAsDefault(" . $lang . ") method ...");
		$db = \App\Db\Db::getInstance();
		$prefix = $lang['prefix'];
		$fileName = 'config/config.inc.php';
		$completeData = file_get_contents($fileName);
		$updatedFields = "default_language";
		$patternString = "\$%s = '%s';";
		$pattern = '/\$' . $updatedFields . '[\s]+=([^;]+);/';
		$replacement = sprintf($patternString, $updatedFields, ltrim($prefix, '0'));
		$fileContent = preg_replace($pattern, $replacement, $completeData);
		$filePointer = fopen($fileName, 'w');
		fwrite($filePointer, $fileContent);
		fclose($filePointer);
		$dataReader = (new \App\Db\Query)->select('prefix')
				->from('vtiger_language')
				->where(['isdefault' => 1])
				->createCommand()->query();
		if ($dataReader->count() == 1) {
			$prefixOld = $dataReader->readColumn(0);
			$db->createCommand()->update('vtiger_language', ['isdefault' => 0], ['isdefault' => 1])->execute();
		}
		$status = $db->createCommand()->update('vtiger_language', ['isdefault' => 1], ['prefix' => $prefix])->execute();
		if ($status)
			$status = true;
		else
			$status = false;
		\App\Log\Log::trace("Exiting \App\Modules\Settings\LangManagement\Models\Module::setAsDefault() method ...");
		return array('success' => $status, 'prefixOld' => $prefixOld);
	}

	public function getStatsData($langBase, $langs, $byModule = false)
	{
		$filesName = $this->getModFromLang($langBase);
		if (strpos($langs, $langBase) === false) {
			$langs .= ',' . $langBase;
		}
		$data = [];
		foreach ($filesName as $gropu) {
			foreach ($gropu as $mode => $name) {
				if ($byModule === false || $byModule === $mode) {
					$data[$mode] = $this->getStats($this->loadLangTranslation($langs, $mode), $langBase, $byModule);
				}
			}
		}
		return $data;
	}

	public function getStats($data, $langBase, $byModule)
	{
		$differences = [];
		$i = 0;
		foreach ($data as $id => $dataLang) {
			if (!in_array($id, ['php', 'js']))
				continue;
			foreach ($dataLang as $key => $langs) {
				foreach ($langs as $lang => $value) {
					if ($lang == $langBase) {
						++$i;
						continue;
					}
					if (!empty($langs[$langBase]) && ($value == $langs[$langBase] || empty($value))) {
						if ($byModule !== false) {
							$differences[$id][$key][$langBase] = $langs[$langBase];
							$differences[$id][$key][$lang] = $value;
						} else {
							$differences[$lang][] = $key;
						}
					}
				}
			}
		}
		if ($byModule === false) {
			array_unshift($differences, $i);
		}
		return $differences;
	}
}
