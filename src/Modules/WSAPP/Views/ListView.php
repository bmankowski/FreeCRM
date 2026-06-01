<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * WSAPP has no CRM UI; standard ListView URLs route to Home.
 */

declare(strict_types=1);

namespace App\Modules\WSAPP\Views;

class ListView extends \App\Modules\Base\Views\Index
{
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		header('Location: index.php?module=Home&view=Index');
		exit;
	}
}
