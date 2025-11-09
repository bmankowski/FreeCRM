<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * author bmankowski@gmail.com
 * copyright (c) FreeCRM
 * license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace vtlib;

/**
 * Backward compatibility adapter for vtlib\LinkData.
 */
class LinkData
{
	protected array $input;
	protected Link $link;
	protected $user;
	protected $module;

	public function __construct(Link $link, $user, ?array $input = null)
	{
		$this->link = $link;
		$this->user = $user;
		$this->input = $input ?? [];
		$this->module = vglobal('currentModule');
	}

	/**
	 * Get input parameter value.
	 *
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	public function getInputParameter(string $name)
	{
		return $this->input[$name] ?? null;
	}

	/**
	 * Get link instance.
	 */
	public function getLink(): Link
	{
		return $this->link;
	}

	/**
	 * Get user.
	 *
	 * @return mixed
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Get module name.
	 *
	 * @return mixed
	 */
	public function getModule()
	{
		return $this->module;
	}
}

