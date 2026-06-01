<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * ModTracker has no record edit form; standard Edit URLs route to Settings.
 */

declare(strict_types=1);

namespace App\Modules\ModTracker\Views;

class Edit extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('ModTracker');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
