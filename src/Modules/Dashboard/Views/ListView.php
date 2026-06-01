<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Dashboard utility module has no CRM list; standard ListView URLs route to Home.
 */

declare(strict_types=1);

namespace App\Modules\Dashboard\Views;

class ListView extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('Dashboard');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
