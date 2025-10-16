<?php

namespace FreeCRM\Modules\Vtiger\UiTypes;

/**
 * UIType Company Field Class
 * @package YetiForce.UIType
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class CompanySelect extends Base
{

	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/CompanySelect.tpl';
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $tree
	 * @param int $record
	 * @param \FreeCRM\Modules\Vtiger\Models\Record $recordInstance
	 * @param boolean $rawText
	 * @return string
	 */
	public function getDisplayValue($values, $record = false, $recordInstance = false, $rawText = false)
	{
		$namesOfCompany = '';
		if (!empty($values)) {
			$companiesList = $this->getPicklistValues();
			$namesOfCompany = $companiesList[$values[0]]['name'];
		}
		return $namesOfCompany;
	}

	/**
	 * Function to get all the available picklist values for the company
	 * @return array List of picklist values if the field
	 */
	public function getPicklistValues()
	{
		return \FreeCRM\Modules\Settings\Companies\Models\Module::getAllCompanies();
	}

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getListSearchTemplateName()
	{
		return 'uitypes/CompanySelectFieldSearchView.tpl';
	}
}
