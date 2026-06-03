<?php

namespace App\Modules\Settings\Mail\Models;
use App\Modules\Settings\Base\Models\MenuItem;



/**
 * Mail record model class
 * @package YetiForce.Settings.Record
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

class Record extends \App\Modules\Settings\Base\Models\Record
{

	/**
	 * Function to get the Id
	 * @return int Id
	 */
	public function getId()
	{
		return $this->get('id');
	}

	/**
	 * Function to get the Name
	 * @return string
	 */
	public function getName()
	{
		return $this->get('name');
	}

	/**
	 * Function to get the Delete Action Url
	 * @return string URL
	 */
	public function getDeleteActionUrl()
	{
		return 'index.php?module=Mail&parent=Settings&action=DeleteAjax&record=' . $this->getId();
	}

	/**
	 * Function to get the Acceptance Action Url
	 * @return string URL
	 */
	public function getAcceptanceActionUrl()
	{
		return 'index.php?module=Mail&parent=Settings&action=SaveAjax&mode=acceptanceRecord&record=' . $this->getId();
	}

	/**
	 * Function to get the Detail Url
	 * @return string URL
	 */
	public function getDetailViewUrl()
	{
		$menu = \App\Modules\Settings\Base\Models\MenuItem::getInstance('LBL_EMAILS_TO_SEND');
		return 'index.php?module=Mail&parent=Settings&view=Detail&record=' . $this->getId() . '&fieldid=' . $menu->get('fieldid');
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $key
	 * @return string
	 */
	public function getDisplayValue(string $key): string
	{
		$value = $this->get($key);
		switch ($key) {
			case 'smtp_id':
				$smtpName = \App\Email\Mail::getSmtpById($value)['name'];
				$value = '<a href=index.php?module=MailSmtp&parent=Settings&view=Detail&record=' . $value . '>' . $smtpName . '</a>';
				break;
			case 'status':
				if (isset(\App\Email\Mailer::$statuses[$value])) {
					$value = \App\Runtime\Vtiger_Language_Handler::translate(\App\Email\Mailer::$statuses[$value], 'Settings::Mail');
				}
				break;
			case 'owner':
				$value = \App\Fields\Owner::getUserLabel($value);
				break;
			case 'content':
				$value = \vtlib\Functions:: getHtmlOrPlainText($value);
				break;
			case 'date':
				$value = \App\Fields\DateTimeField::convertToUserFormat($value);
				break;
			case 'from':
			case 'to':
			case 'cc':
			case 'bcc':
				$value = $this->getDisplayValueForEmail($value);
				break;
			case 'attachments':
				if ($value) {
					$attachments = $value;
					$value = '';
					$fileCounter = 0;
					foreach (\App\Utils\Json::decode($attachments) as $path => $name) {
						if (is_numeric($path)) {
							$path = $name;
							$name = 'LBL_FILE';
						}
						$actionPath = "?module=Mail&parent=Settings&action=DownloadAttachment&record={$this->getId()}&selectedFile=$fileCounter";
						$value .= "<a href=\"$actionPath\" title=\"$path\">$name</a>, ";
						$fileCounter++;
					}

					$value = rtrim($value, ', ');
				}
				break;
		}
		return (string) ($value ?? '');
	}

	/**
	 * Function to get the display value for emails
	 * @param array $emails
	 * @return string
	 */
	public function getDisplayValueForEmail($emails)
	{
		$value = '';
		if ($emails) {
			foreach (\App\Utils\Json::decode($emails) as $email => $name) {
				if (is_numeric($email)) {
					$email = $name;
					$name = '';
					$value .= $email . ', ';
				} else {
					$value .= $name . ' &lt;' . $email . '&gt;, ';
				}
			}
		}
		return rtrim($value, ', ');
	}

	/**
	 * Function to delete the current Record Model
	 */
	public function delete()
	{
		\App\Db\Db::getInstance('admin')->createCommand()
			->delete('s_#__mail_queue', ['id' => $this->getId()])
			->execute();
	}

	/**
	 * Function to get the list view actions for the record
	 * @return array - Associate array of \App\Modules\Base\Models\Link instances
	 */
	public function getRecordLinks()
	{
		$links = [];
		if ($this->get('status') === 0) {
			$recordLinks[] = [
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_ACCEPTANCE_RECORD',
				'linkurl' => '#',
				'linkicon' => 'glyphicon glyphicon-ok',
				'linkclass' => 'btn btn-xs btn-success acceptanceRecord'
			];
		}

		$recordLinks[] = [
			'linktype' => 'LISTVIEWRECORD',
			'linklabel' => 'LBL_DELETE_RECORD',
			'linkurl' => $this->getDeleteActionUrl(),
			'linkicon' => 'glyphicon glyphicon-trash',
			'linkclass' => 'btn btn-xs btn-danger'
		];

		foreach ($recordLinks as &$recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}
		return $links;
	}

	/**
	 * Function to get the instance of advanced permission record model
	 * @param int $id
	 * @return \self instance, if exists.
	 */
	public static function getInstance($id)
	{
		$query = (new \App\Db\Query())->from('s_#__mail_queue')->where(['id' => $id]);
		$row = $query->createCommand(\App\Db\Db::getInstance('admin'))->queryOne();
		$instance = false;
		if ($row !== false) {
			$instance = new self();
			$instance->setData($row);
		}
		return $instance;
	}
}
