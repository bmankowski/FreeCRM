<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * ImportManager has no CRM list; standard ListView URLs route to the wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Views;

class ListView extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('ImportManager');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
