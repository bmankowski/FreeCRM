<?php

namespace App\Modules\Base\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Vtiger Image Model Class
 */
class Image extends \App\Runtime\BaseModel
{

	/**
	 * Function to get the title of the Image
	 * @return string
	 */
	public function getTitle()
	{
		return $this->get('title');
	}

	/**
	 * Function to get the alternative text for the Image
	 * @return string
	 */
	public function getAltText()
	{
		return $this->get('alt');
	}

	/**
	 * Web URL for the image (company logo URL or theme skin asset path).
	 * @return string|false
	 */
	public function getImageWebUrl()
	{
		$imageUrl = $this->get('imageUrl');
		if (!empty($imageUrl)) {
			return (string) $imageUrl;
		}
		$imageName = $this->get('imagename') ?: $this->get('imageName');
		if (empty($imageName)) {
			return false;
		}
		return \App\Runtime\Vtiger_Theme::getThemeImageWebUrl($imageName);
	}

	/**
	 * Function to get the Image file name
	 * @return string
	 */
	public function getImageFileName()
	{
		return $this->get('imagename');
	}
}
