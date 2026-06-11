<?php

namespace App\Modules\Base\Widgets;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class ProductsServices extends \App\Modules\Base\Widgets\Basic
{

	public $allowedModules = ['Accounts'];

	public function getUrl()
	{
		return 'module=Products&view=Widget&fromModule=' . $this->Module . '&record=' . $this->Record . '&mode=showProductsServices&page=1&mod=Products&limit=' . $this->Data['limit'];
	}

	public function getWidget()
	{
		$this->Config['url'] = $this->getUrl();
		$this->Config['tpl'] = 'ProductsServicesBasic.tpl';
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById((int) $this->Record, $this->Module);
		$this->Config['data']['modulesAndCount'] = \App\Modules\Products\Models\SummaryWidget::getModulesAndCount($recordModel);
		return $this->Config;
	}

	public function getConfigTplName()
	{
		return 'ProductsServicesConfig';
	}
}
