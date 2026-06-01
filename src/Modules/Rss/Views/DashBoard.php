<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Rss has no dashboard; standard DashBoard URLs route to ListView.
 */

declare(strict_types=1);

namespace App\Modules\Rss\Views;

class DashBoard extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('Rss');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
