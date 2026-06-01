<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * ApiAddress has no dashboard; standard DashBoard URLs route to Settings.
 */

declare(strict_types=1);

namespace App\Modules\ApiAddress\Views;

class DashBoard extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		header('Location: index.php?module=ApiAddress&parent=Settings&view=Configuration');
		exit;
	}
}
