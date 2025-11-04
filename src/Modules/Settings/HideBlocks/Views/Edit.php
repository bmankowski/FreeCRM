<?php

namespace App\Modules\Settings\HideBlocks\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Edit extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$mode = '';
		$enabled = 0;
		$views = array();
		$blockId = '';
		$viewer = $this->getViewer($request);
		$moduleModel = \App\Modules\Settings\Base\Models\Module::getInstance($qualifiedModuleName);
		if ($recordId) {
			$mode = 'edit';
			$recordModel = \App\Modules\Settings\HideBlocks\Models\Record::getInstanceById($recordId, $qualifiedModuleName);
			$enabled = $recordModel->get('enabled');
			if ($recordModel->get('view') != '')
				$views = explode(',', $recordModel->get('view'));
			$blockId = $recordModel->get('blockid');
		}

		$viewer->assign('MODE', $mode);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('ENABLED', $enabled);
		$viewer->assign('SELECTED_VIEWS', $views);
		$viewer->assign('BLOCK_ID', $blockId);
		$viewer->assign('MODULE', 'HideBlocks');
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('BLOCKS', $moduleModel->getAllBlock());
		$viewer->assign('VIEWS', $moduleModel->getViews());
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}
}
