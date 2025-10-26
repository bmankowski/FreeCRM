<?php
namespace App\View\Assets;

/**
 * Script Asset - Data model for JavaScript assets
 */
class ScriptAsset
{
	const DEFAULT_TYPE = 'text/javascript';

	protected $data = [];

	/**
	 * Set a property on the model and return this for chaining
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;
		return $this;
	}

	/**
	 * Get a property from the model
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->data[$key] ?? null;
	}

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

