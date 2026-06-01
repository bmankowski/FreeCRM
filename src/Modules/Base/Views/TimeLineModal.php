<?php

namespace App\Modules\Base\Views;

/**
 * TimeLineModal View Class
 * @package YetiForce.Modal
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class TimeLineModal  extends \App\Modules\Base\Views\Index
{

	/**
	 * Checking permission
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermittedToRecord
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (!\App\Security\Privilege::isPermitted($moduleName, 'TimeLineList') || !\App\Security\Privilege::isPermitted($moduleName, 'DetailView', $recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	/**
	 * The initial process
	 * @param \App\Http\Vtiger_Request $request
	 * @param bool $display
	 */
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
		echo '<div class="modal-header">
				<button class="close" data-dismiss="modal" title="' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_CLOSE') . '">x</button>
				<h3 class="modal-title">' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_TIMELINE', $request->getModule()) . ' </h3>
			</div>
			<div class="modal-body">';
	}

	/**
	 * The final process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		parent::postProcess($request);
		echo '</div>';
	}

	/**
	 * Proceess
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$request->set('limit', \App\Core\AppConfig::module('ModTracker', 'TIMELINE_IN_LISTVIEW_LIMIT'));
		$request->set('type', \App\Modules\Base\Widgets\HistoryRelation::getActions());
		$request->set('noMore', true);

		$viewClassName = \App\Core\Loader::getComponentClassName('View', 'Detail', $moduleName);
		$instance = new $viewClassName();

		$this->preProcess($request);
		echo $instance->showRecentRelation($request);
		$this->postProcess($request);
	}
}
