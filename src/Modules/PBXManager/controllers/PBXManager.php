<?php

namespace App\Modules\PBXManager;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Controller {

	public function getConnector()
	{
		return new PBXManager_PBXManager_Connector;
	}

	/**
	 * Function to process the request
	 * @params <array> call details
	 * return Response object
	 */
	public function process($request)
	{
		$mode = $request->get('callstatus');

		switch ($mode) {
			case "StartApp" :
				$this->processStartupCall($request);
				break;
			case "DialAnswer" :
				$this->processDialCall($request);
				break;
			case "Record" :
				$this->processRecording($request);
				break;
			case "EndCall" :
				$this->processEndCall($request);
				break;
			case "Hangup" :
				$callCause = $request->get('causetxt');
				if ($callCause == "null") {
					break;
				}
				$this->processHangupCall($request);
				break;
		}
	}

	/**
	 * Function to process Incoming call request
	 * @params <array> incoming call details
	 * return Response object
	 */
	public function processStartupCall($request)
	{
		$connector = $this->getConnector();

		$temp = $request->get('channel');
		$temp = explode("-", $temp);
		$temp = explode("/", $temp[0]);

		$callerNumber = $request->get('callerIdNumber');
		$userInfo = \App\Modules\PBXManager\Models\Record::getUserInfoWithNumber($callerNumber);

		if (!$userInfo) {
			$callerNumber = $temp[1];
			if (is_numeric($callerNumber)) {
				$userInfo = \App\Modules\PBXManager\Models\Record::getUserInfoWithNumber($callerNumber);
			}
		}

		if ($userInfo) {
			// Outbound Call
			$request->set('Direction', 'outbound');

			if ($request->get('callerIdNumber') == $temp[1]) {
				$to = $request->get('callerIdName');
			} else if ($request->get('callerIdNumber')) {
				$to = $request->get('callerIdNumber');
			} else if ($request->get('callerId')) {
				$to = $request->get('callerId');
			}

			$request->set('to', $to);
			$customerInfo = \App\Modules\PBXManager\Models\Record::lookUpRelatedWithNumber($to);
			$connector->handleStartupCall($request, $userInfo, $customerInfo);
		} else {
			// Inbound Call
			$request->set('Direction', 'inbound');
			$customerInfo = \App\Modules\PBXManager\Models\Record::lookUpRelatedWithNumber($request->get('callerIdNumber'));
			$request->set('from', $request->get('callerIdNumber'));
			$connector->handleStartupCall($request, $userInfo, $customerInfo);
		}
	}

	/**
	 * Function to process Dial call request
	 * @params <array> Dial call details
	 * return Response object
	 */
	public function processDialCall($request)
	{
		$connector = $this->getConnector();
		$connector->handleDialCall($request);
	}

	/**
	 * Function to process EndCall event
	 * @params <array> Dial call details
	 * return Response object
	 */
	public function processEndCall($request)
	{
		$connector = $this->getConnector();
		$connector->handleEndCall($request);
	}

	/**
	 * Function to process Hangup call request
	 * @params <array> Hangup call details
	 * return Response object
	 */
	public function processHangupCall($request)
	{
		$connector = $this->getConnector();
		$connector->handleHangupCall($request);
	}

	/**
	 * Function to process recording
	 * @params <array> recording details
	 * return Response object
	 */
	public function processRecording($request)
	{
		$connector = $this->getConnector();
		$connector->handleRecording($request);
	}
}
