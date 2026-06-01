<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Password has no record edit form; standard Edit URLs route to Settings.
 */

declare(strict_types=1);

namespace App\Modules\Password\Views;

class Edit extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		header('Location: index.php?module=Password&parent=Settings&view=Index');
		exit;
	}
}
