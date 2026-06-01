<?php

namespace App\Modules\ApiAddress\Views;

class ListView extends \App\Modules\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		header('Location: index.php?module=ApiAddress&parent=Settings&view=Configuration');
		exit;
	}
}
