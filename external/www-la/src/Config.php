<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class Config
{
	/** @return array<string, mixed> */
	public static function load(string $root): array
	{
		$configPath = null;
		foreach ([
			$root . '/../private/la/config.php',
			$root . '/_link_action/config.php',
		] as $candidate) {
			if (is_readable($candidate)) {
				$configPath = $candidate;
				break;
			}
		}
		if ($configPath === null) {
			$configPath = $root . '/config.example.php';
		}

		return require $configPath;
	}
}
