<?php

namespace App\Modules\Settings\Mail\Models;

class Autologin
{
	public function getAccountsList(): array
	{
		return [];
	}

	public function getAutologinUsers($userId): array
	{
		return [];
	}

	public function updateUsersAutologin($id, $users): void
	{
	}

	public static function getInstance(): self
	{
		return new self();
	}
}
