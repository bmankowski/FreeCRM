<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Home has no record edit form; standard Edit URLs route to Index.
 */

declare(strict_types=1);

namespace App\Modules\Home\Views;

class Edit extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('Home');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
