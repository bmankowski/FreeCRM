<?php
namespace App\View\Assets;

/**
 * Asset Path Resolver - Static utilities for resolving asset file paths
 */
class AssetPathResolver
{
	/**
	 * Function to get the path of a given asset file
	 * @param string $fileName
	 * @return string|false - file path, false if not exists
	 */
	public static function getFilePath($fileName = '')
	{
		if ($fileName === null || $fileName === '') {
			return false;
		}

		$filePath = self::getBaseLayoutPath() . '/' . $fileName;
		$completeFilePath = \App\Core\Loader::resolveNameToPath('~' . $filePath);

		if (file_exists($completeFilePath)) {
			return $filePath;
		}

		return false;
	}

	/**
	 * Function to get the base layout path
	 * @return string - layout folder path
	 */
	public static function getBaseLayoutPath()
	{
		return 'layouts/' . self::getLayoutName();
	}

	/**
	 * Function to get the current layout name
	 * @return string - layout name
	 */
	public static function getLayoutName()
	{
		return \App\Runtime\CRM_Viewer::getLayoutName();
	}
}

