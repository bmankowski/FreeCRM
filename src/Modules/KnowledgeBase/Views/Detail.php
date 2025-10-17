<?php

namespace App\Modules\KnowledgeBase\Views;

/**
 * Detail View for KnowledgeBase
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Krzysztof Gastołek <krzysztof.gastolek@wars.pl>
 */

use App\Http\Vtiger_Request;
class Detail extends \Vtiger_Index_View
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showPreview');
	}

	public function showPreview($request)
	{
		$previewContent = new KnowledgeBase_PreviewContent_View();
		$previewContent->process($request);
	}
}
