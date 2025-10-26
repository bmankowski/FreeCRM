<?php
namespace App\View\Assets;

/**
 * Style Asset - Data model for CSS assets
 */
class StyleAsset
{
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
	 * Get the href URL
	 * @return string
	 */
	public function getHref()
	{
		return $this->get('href');
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

