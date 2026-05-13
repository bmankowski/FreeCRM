<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\Settings\TemplateDynamicElements\Models;

/**
 * List view model for PDF dynamic elements.
 */
class ListView extends \App\Modules\Settings\Base\Models\ListView
{
	public function getBasicListQuery()
	{
		return parent::getBasicListQuery()->orderBy(['sequence' => SORT_ASC, 'label' => SORT_ASC]);
	}
}
