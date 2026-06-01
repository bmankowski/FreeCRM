<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * OSSMailScanner UI lives under Settings; module-level Index URLs route there.
 */

declare(strict_types=1);

namespace App\Modules\OSSMailScanner\Views;

class Index extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('OSSMailScanner');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
