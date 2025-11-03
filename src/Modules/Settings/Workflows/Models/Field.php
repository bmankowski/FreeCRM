<?php

namespace App\Modules\Settings\Workflows\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Field extends \App\Modules\Base\Models\Field
{

	/**
	 * Function to get all the supported advanced filter operations
	 * @return <Array>
	 */
	public static function getAdvancedFilterOptions()
	{
		return \App\Modules\Base\Helpers\AdvancedFilter::getAdvancedFilterOptions();
	}

	/**
	 * Function to get the advanced filter option names by Field type
	 * @return <Array>
	 */
	public static function getAdvancedFilterOpsByFieldType()
	{
		return \App\Modules\Base\Helpers\AdvancedFilter::getAdvancedFilterOpsByFieldType();
	}

	/**
	 * Function to get comment field which will useful in creating conditions
	 * @param \App\Modules\Base\Models\Module $moduleModel
	 * @return <\App\Modules\Base\Models\Field>
	 */
	public static function getCommentFieldForFilterConditions($moduleModel)
	{
		$commentField = new \App\Modules\Base\Models\Field();
		$commentField->set('name', '_VT_add_comment');
		$commentField->set('label', 'Comment');
		$commentField->setModule($moduleModel);
		$commentField->fieldDataType = 'comment';

		return $commentField;
	}

	/**
	 * Function to get comment fields list which are useful in tasks
	 * @param \App\Modules\Base\Models\Module $moduleModel
	 * @return <Array> list of Field models <\App\Modules\Base\Models\Field>
	 */
	public static function getCommentFieldsListForTasks($moduleModel)
	{
		$commentsFieldsInfo = array('$(record : Comments 1)$' => 'Last Comment', 'last5Comments' => '$(record : Comments 5)$', 'allComments' => '$(record : Comments)$');

		$commentFieldModelsList = array();
		foreach ($commentsFieldsInfo as $fieldName => $fieldLabel) {
			$commentField = new \App\Modules\Base\Models\Field();
			$commentField->setModule($moduleModel);
			$commentField->set('name', $fieldName);
			$commentField->set('label', $fieldLabel);
			$commentFieldModelsList[$fieldName] = $commentField;
		}
		return $commentFieldModelsList;
	}
}
