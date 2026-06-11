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
			$userId = (int) \App\User\CurrentUser::getId();
			$this->Config['data']['canUserSend'] = \App\Modules\Mail\Models\Module::canUserSend($userId);
			if ($this->Config['data']['canUserSend']) {
				$this->Config['data']['composeUrl'] = \App\Modules\Mail\Models\Module::getComposeUrl($this->Module, (int) $this->Record);
			}
			$widget = $this->Config;
		}
		return $widget;
	}
}
