<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * RecycleBin has no record edit form; standard Edit URLs route to ListView.
 */

declare(strict_types=1);

namespace App\Modules\RecycleBin\Views;

class Edit extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('RecycleBin');
		header('Location: ' . $moduleModel->getDefaultUrl());
		exit;
	}
}
