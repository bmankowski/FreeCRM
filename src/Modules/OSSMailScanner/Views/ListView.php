<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * OSSMailScanner has no CRM list; standard ListView URLs route to Settings.
 */

declare(strict_types=1);

namespace App\Modules\OSSMailScanner\Views;

class ListView extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('OSSMailScanner');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
