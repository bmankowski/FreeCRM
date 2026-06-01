<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * OpenStreetMap has no record edit form; standard Edit URLs route to the map UI.
 */

declare(strict_types=1);

namespace App\Modules\OpenStreetMap\Views;

class Edit extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('OpenStreetMap');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
