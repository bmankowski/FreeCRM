<?php

namespace App\Modules\Base\Widgets;

class EmailList extends \App\Modules\Base\Widgets\Basic
{

	public $dbParams = array();

	public function getUrl()
	{
		return 'module=Mail&view=Widget&smodule=' . $this->Module . '&srecord=' . $this->Record . '&mode=showEmailsList&type=All&limit=' . $this->Data['limit'];
	}

	public function getConfigTplName()
	{
		return 'EmailListConfig';
	}

	public function getWidget()
	{
		$widget = [];
		if (\App\Core\AppConfig::main('isActiveSendingMails')) {
			$this->Config['tpl'] = 'EmailList.tpl';
			$this->Config['url'] = $this->getUrl();
			$widget = $this->Config;
		}
		return $widget;
	}
}
