<?php
namespace App\View\Assets;

/**
 * Style Asset - Data model for CSS assets
 */
class StyleAsset extends BaseAsset
{
	/**
	 * Get the href URL
	 * @return string
	 */
	public function getHref()
	{
		return $this->get('href') ?? $this->get('linkurl');
	}

	/**
	 * Get the rel attribute
	 * @return string
	 */
	public function getRel()
	{
		return $this->get('rel') ?? 'stylesheet';
	}

	/**
	 * Get the media attribute
	 * @return string
	 */
	public function getMedia()
	{
		return $this->get('media') ?? 'screen';
	}
}

