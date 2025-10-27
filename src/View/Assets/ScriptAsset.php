<?php
namespace App\View\Assets;

/**
 * Script Asset - Data model for JavaScript assets
 */
class ScriptAsset extends BaseAsset
{
	const DEFAULT_TYPE = 'text/javascript';

	/**
	 * Get the source URL
	 * @return string
	 */
	public function getSrc()
	{
		return $this->get('src') ?? $this->get('linkurl');
	}

	/**
	 * Get the script type
	 * @return string
	 */
	public function getType()
	{
		return $this->get('type') ?? self::DEFAULT_TYPE;
	}
}

