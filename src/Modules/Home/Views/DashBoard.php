<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Home dashboard lives at Index; standard DashBoard URLs route there.
 */

declare(strict_types=1);

namespace App\Modules\Home\Views;

class DashBoard extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('Home');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
