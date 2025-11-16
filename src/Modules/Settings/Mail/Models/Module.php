<?php

namespace App\Modules\Settings\Mail\Models;
use App\Modules\Settings\Base\Models\MenuItem;



/**
 * Mail module model class
 * @package YetiForce.Settings.Module
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class Module extends \App\Modules\Settings\Base\Models\Module
{

	public $baseTable = 's_#__mail_queue';
	public $baseIndex = 'id';
	public $listFields = ['smtp_id' => 'LBL_SMTP_NAME', 'date' => 'LBL_DATE', 'owner' => 'LBL_CREATED_BY', 'subject' => 'LBL_SUBJECT', 'status' => 'LBL_STATUS', 'priority' => 'LBL_PRIORITY'];
	public $name = 'Mail';
	public $filterFields = ['smtp_id', 'status', 'priority'];

	/**
	 * Function to get the url for default view of the module
	 * @return string URL
	 */
	public function getDefaultUrl()
	{
		$menu = \App\Modules\Settings\Base\Models\MenuItem::getInstance('LBL_EMAILS_TO_SEND');
		return 'index.php?module=Mail&parent=Settings&view=ListView&fieldid=' . $menu->get('fieldid');
	}

	/**
	 * Function to get the url for create view of the module
	 * @return string URL
	 */
	public function getCreateRecordUrl()
	{
		return '';
	}

	public function getFilterFields()
	{
		return $this->filterFields;
	}

	/**
	 * Function to get the url for attachment file
	 * @param int $id
	 * @param int $selectedFile
	 * @return string URL
	 */
	public static function getAttachmentPath($id, $selectedFile)
	{
		$path = '';
		$attachments = (new \App\Db\Query())->select(['attachments'])->from('s_#__mail_queue')->where(['id' => $id])->scalar(\App\Db::getInstance('admin'));
		$counter = 0;
		foreach (\App\Utils\Json::decode($attachments) as $path => $name) {
			if ($counter === $selectedFile) {
				return $path;
			}
			$counter++;
		}
		return $path;
	}
}
