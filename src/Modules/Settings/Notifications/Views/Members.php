<?php

namespace App\Modules\Settings\Notifications\Views;



/**
 * Members View Class for Notifications
 * @package YetiForce.Settings.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Members extends \App\Modules\Settings\Base\Views\BasicModal
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('addWatchingMembers');
		$this->exposeMethod('exceptions');
	}

	/**
	 * Function gets settings
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		$this->addWatchingMembers($request);
	}

	/**
	 * Function downloads settings for watched members
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function addWatchingMembers(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$srcModule = $request->get('srcModule');
		$watchdogModel = \App\Modules\Base\Models\Watchdog::getInstance($srcModule);
		$viewer = $this->getViewer($request);
		$viewer->assign('IS_TO_ADD', true);
		$viewer->assign('SRC_MODULE', $srcModule);
		$viewer->assign('RESTRICT_MEMBERS', $watchdogModel->getWatchingMembers());
		$this->preProcess($request);
		
		// Prepare Notifications Members-specific data for Members template
		$this->prepareNotificationsMembersData($viewer);
		
		$viewer->view('Members.tpl', $moduleName);
		$this->postProcess($request);
	}
	
	/**
	 * Prepare data for Notifications Members template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareNotificationsMembersData($viewer)
	{
		$viewer->assign('PRIVILEGE_MEMBERS', \App\Security\PrivilegeUtil::getMembers());
	}

	/**
	 * Function downloads settings for exceptions
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function exceptions(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$srcModule = $request->get('srcModule');
		$member = $request->get('member');
		$watchdogModel = \App\Modules\Base\Models\Watchdog::getInstance($srcModule);
		$viewer = $this->getViewer($request);
		$viewer->assign('MEMBER', $member);
		$viewer->assign('SRC_MODULE', $srcModule);
		$viewer->assign('MEMBERS', $watchdogModel->getWatchingExceptions($member));
		$this->preProcess($request);
		
		// Prepare Notifications MembersExceptions-specific data for MembersExceptions template
		$this->prepareNotificationsMembersExceptionsData($viewer, $member);
		
		$viewer->view('MembersExceptions.tpl', $moduleName);
		$this->postProcess($request);
	}
	
	/**
	 * Prepare data for Notifications MembersExceptions template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareNotificationsMembersExceptionsData($viewer, $member)
	{
		$userIds = \App\Security\PrivilegeUtil::getUserByMember($member);
		$userLabels = [];
		foreach ($userIds as $userId) {
			$userLabels[$userId] = \App\Fields\Owner::getUserLabel($userId);
		}
		$viewer->assign('USER_IDS', $userIds);
		$viewer->assign('USER_LABELS', $userLabels);
	}
}
