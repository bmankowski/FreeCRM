<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace vtlib;

/**
 * Utils adapter class.
 * 
 * Minimal adapter for vtlib\Utils.
 * Implements only the methods needed by adapters.
 */
class Utils
{
	/**
	 * Check if given value is a number or not
	 * @param mixed $value String or Integer
	 * @return bool|int
	 */
	static function isNumber($value)
	{
		return is_numeric($value) ? intval($value) == $value : false;
	}

	/**
	 * Function to check the file access is made within web root directory as well as is safe for php inclusion
	 * @param string $filepath File path to check
	 * @param bool $dieOnFail Die on failure
	 * @return bool
	 */
	static function checkFileAccessForInclusion($filepath, $dieOnFail = true)
	{
		$unsafeDirectories = array('storage', 'cache', 'test');
		$realfilepath = realpath($filepath);

		/** Replace all \\ with \ first */
		$realfilepath = str_replace('\\\\', '\\', $realfilepath);
		$rootdirpath = str_replace('\\\\', '\\', ROOT_DIRECTORY . DIRECTORY_SEPARATOR);

		/** Replace all \ with / now */
		$realfilepath = str_replace('\\', '/', $realfilepath);
		$rootdirpath = str_replace('\\', '/', $rootdirpath);

		$relativeFilePath = str_replace($rootdirpath, '', $realfilepath);
		$filePathParts = explode('/', $relativeFilePath);

		if (stripos($realfilepath, $rootdirpath) !== 0 || in_array($filePathParts[0], $unsafeDirectories)) {
			if ($dieOnFail) {
				\App\Log\Log::error(__METHOD__ . '(' . $filepath . ') - Sorry! Attempt to access restricted file. realfilepath: ' . print_r($realfilepath, true));
				throw new \App\Exceptions\AppException('Sorry! Attempt to access restricted file.');
			}
			return false;
		}
		return true;
	}

	/**
	 * Helper function to log messages
	 * @param string $message Message to log
	 * @param bool $delimit true appends linebreak, false to avoid it
	 */
	static function Log($message, $delimit = true)
	{
		\App\Log\Log::trace($message);
	}

	/**
	 * Check if table exists in database
	 * @param string $tableName Table name to check
	 * @return bool True if table exists
	 */
	static function CheckTable($tableName)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		return (bool) $adb->checkExistTable($tableName);
	}

	/**
	 * Create a table in the database
	 * @param string $tableName Table name to create
	 * @param string $sqlDefinition SQL CREATE TABLE definition (columns, constraints, etc.)
	 * @param bool $ignoreIfExists If true, don't create if table already exists
	 * @return bool True on success
	 */
	static function CreateTable($tableName, $sqlDefinition, $ignoreIfExists = false)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		
		// Check if table already exists
		if ($ignoreIfExists && $adb->checkExistTable($tableName)) {
			return true;
		}
		
		// Create the table
		$sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` {$sqlDefinition}";
		$result = $adb->query($sql);
		return $result !== false;
	}
}

