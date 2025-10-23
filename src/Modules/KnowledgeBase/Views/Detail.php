<?php

namespace App\Modules\KnowledgeBase\Views;

/**
 * Detail View for KnowledgeBase
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Krzysztof Gastołek <krzysztof.gastolek@wars.pl>
 */

use App\Http\Vtiger_Request;
class Detail  extends \App\Modules\Vtiger\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showPreview');
	}

	public function showPreview($request)
	{
		$previewContent = new \App\Modules\KnowledgeBase\Views\PreviewContent();
		$previewContent->process($request);
	}
}
