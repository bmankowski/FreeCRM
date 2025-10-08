<?php

/**
 * Detail View for KnowledgeBase
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Krzysztof Gastołek <krzysztof.gastolek@wars.pl>
 */

use FreeCRM\Http\Vtiger_Request;
class KnowledgeBase_Detail_View extends Vtiger_Detail_View
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
