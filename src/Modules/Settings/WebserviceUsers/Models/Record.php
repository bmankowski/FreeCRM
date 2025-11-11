<?php

namespace App\Modules\Settings\WebserviceUsers\Models;
use App\Modules\Settings\Base\Models\Field;



/**
 * Record Model
 * @package YetiForce.Settings.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */


class Record extends \App\Modules\Settings\Base\Models\Record
{

	/**
	 * Edit fields 
	 * @var string[] 
	 */
	private $editFields = ['Portal' => [
			'server_id' => 'FL_SERVER', 'status' => 'FL_STATUS', 'user_name' => 'FL_LOGIN', 'password_t' => 'FL_PASSWORD', 'type' => 'FL_TYPE', 'language' => 'FL_LANGUAGE', 'crmid' => 'FL_RECORD_NAME', 'user_id' => 'FL_USER']
	];

	/**
	 * Record ID
	 * @return int
	 */
	public function getId()
	{
		return $this->get('id');
	}

	/**
	 * Record name
	 * @return string
	 */
	public function getName()
	{
		return $this->get('name');
	}

	/**
	 * Function to get Module instance
	 * @return Settings_Webservice\App\Modules\Users\Models\Module
	 */
	public function getModule()
	{
		if (!$this->module) {
			$this->module = $moduleInstance = \App\Modules\Settings\Base\Models\Module::getInstance('Settings:WebserviceUsers');
		}
		return $this->module;
	}

	/**
	 * Function to set Module instance
	 * @param Settings_Webservice\App\Modules\Users\Models\Module $moduleModel
	 * @return $this
	 */
	public function setModule($moduleModel)
	{
		$this->module = $moduleModel;
		return $this;
	}

	/**
	 * Function determines fields available in edition view
	 * @return string[]
	 */
	public function getEditFields()
	{
		return $this->editFields[$this->getModule()->typeApi];
	}

	/**
	 * Function determines fields available in edition view
	 * @return string[]
	 */
	public function getFieldInstanceByName($name)
	{
		$moduleName = $this->getModule()->getName(true);
		$fieldsLabel = $this->getEditFields();
		$params = ['uitype' => 1, 'column' => $name, 'name' => $name, 'label' => $fields[$name], 'displaytype' => 1, 'typeofdata' => 'V~M', 'presence' => 0, 'isEditableReadOnly' => false];
		switch ($name) {
			case 'crmid':
				$params['uitype'] = 10;
				$params['referenceList'] = ['Contacts'];
				break;
			case 'status':
				$params['uitype'] = 16;
				$params['picklistValues'] = [1 => \App\Runtime\Vtiger_Language_Handler::translate('PLL_ACTIVE', $moduleName), 0 => \App\Runtime\Vtiger_Language_Handler::translate('PLL_INACTIVE', $moduleName)];
				break;
			case 'server_id':
				$servers = \App\Modules\Settings\WebserviceApps\Models\Module::getActiveServers($this->getModule()->typeApi);
				$params['uitype'] = 16;
				foreach ($servers as $key => $value) {
					$params['picklistValues'][$key] = $value['name'];
				}
				break;
			case 'type':
				$params['uitype'] = 16;
				$params['picklistValues'] = [];
				foreach ($this->getTypeValues() as $key => $value) {
					$params['picklistValues'][$key] = \App\Runtime\Vtiger_Language_Handler::translate($value, $moduleName);
				}
				break;
			case 'language':
				$params['typeofdata'] = 'V~O';
				$params['uitype'] = 32;
				$params['picklistValues'] = vtlib\Language::getAll();
				break;
			case 'user_id':
				$params['uitype'] = 16;
				$params['picklistValues'] = \App\Fields\Owner::getInstance($moduleName)->getAccessibleUsers('', 'owner');
				break;
			case 'password_t':
				$params['typeofdata'] = 'P~M';
				break;
			default:
				break;
		}
		return \App\Modules\Settings\Base\Models\Field::init($moduleName, $params);
	}

	/**
	 * Function to get the instance, given id
	 * @param int $id
	 * @param string $type
	 * @return \self
	 */
	public static function getInstanceById($id, $type)
	{
		$cacheName = get_class();
		if (\App\Cache\Cache::has($cacheName, $id)) {
			return \App\Cache\Cache::get($cacheName, $id);
		}
		$instance = self::getCleanInstance($type);
		$data = (new \App\Db\Query())
			->from($instance->getModule()->getBaseTable())
			->where([$instance->getModule()->getTableIndex() => $id])
			->one(\App\Db::getInstance('webservice'));
		$instance->setData($data);
		\App\Cache\Cache::save($cacheName, $id, $instance);
		return $instance;
	}

	/**
	 * Function to get the clean instance
	 * @param string $type
	 * @return \self
	 */
	public static function getCleanInstance($type)
	{
		$moduleInstance = \App\Modules\Settings\Base\Models\Module::getInstance('Settings:WebserviceUsers');
		$moduleInstance->typeApi = $type;
		$instance = new self();
		$instance->module = $moduleInstance;
		return $instance;
	}

	/**
	 * Function to save
	 * @param array $data
	 * @return boolean
	 */
	public function save($data)
	{
		$db = \App\Db::getInstance('webservice');
		$table = $this->getModule()->getBaseTable();
		$index = $this->getModule()->getTableIndex();
		$fields = $this->getEditFields();
		foreach ($data as $key => $value) {
			if (isset($fields[$key])) {
				$data[$key] = $this->getValueToSave($key, $value);
			} else {
				unset($data[$key]);
			}
		}
		if (empty($this->getId())) {
			$seccess = $db->createCommand()->insert($table, $data)->execute();
			if ($seccess) {
				$this->set('id', $db->getLastInsertID("$table_$index_seq"));
			}
		} else {
			$seccess = $db->createCommand()->update($table, $data, [$index => $this->getId()])->execute();
		}
		return $seccess;
	}

	/**
	 * Function formats data for saving
	 * @param string $key
	 * @param mixed $value
	 * @return int|string
	 */
	private function getValueToSave($key, $value)
	{
		switch ($key) {
			case 'server_id':
			case 'status':
			case 'type':
			case 'crmid':
			case 'user_id':
				$value = (int) $value;
				break;
			default:
				break;
		}
		return $value;
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $name
	 * @return string
	 */
	public function getDisplayValue($name)
	{
		switch ($name) {
			case 'server_id':
				$servers = \App\Modules\Settings\WebserviceApps\Models\Record::getInstanceById($this->get($name));
				return $servers ? $servers->getName() : '<span class="redColor">ERROR</span>';
			case 'crmid':
				return $this->get($name) ? \App\Record::getLabel($this->get($name)) : '';
			case 'status':
				return empty($this->get($name)) ? 'PLL_INACTIVE' : 'PLL_ACTIVE';
			case 'user_id':
				return \App\Fields\Owner::getLabel($this->get($name));
			case 'language':
				return vtlib\Language::getAll()[$this->get($name)];
			case 'type':
				$label = \App\Runtime\Vtiger_Language_Handler::translate($this->getTypeValues($this->get($name)), $this->getModule()->getName(true));
				return \vtlib\Functions:: textLength($label);
			default:
				break;
		}
		return $this->get($name);
	}

	/**
	 * Function to get the list view actions for the record
	 * @return \App\Modules\Base\Models\Link[] - Associate array of \App\Modules\Base\Models\Link instances
	 */
	public function getRecordLinks()
	{
		$links = [];
		$recordLinks = [
				[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT_RECORD',
				'linkurl' => $this->getModule()->getEditViewUrl() . '&record=' . $this->getId(),
				'linkicon' => 'glyphicon glyphicon-pencil',
				'linkclass' => 'btn btn-sm btn-primary',
				'modalView' => true
			],
				[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => 'javascript:\Vtiger_List_Js.deleteRecord(' . $this->getId() . ');',
				'linkicon' => 'glyphicon glyphicon-trash',
				'linkclass' => 'btn btn-sm btn-danger'
			]
		];
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}

		return $links;
	}

	/**
	 * Type field values
	 * @param type $value
	 * @return string
	 */
	public function getTypeValues($value = false)
	{
		$data = [
			1 => 'PLL_USER_PERMISSIONS',
			2 => 'PLL_ACCOUNTS_RELATED_RECORDS',
			3 => 'PLL_ACCOUNTS_RELATED_RECORDS_AND_LOWER_IN_HIERARCHY',
			4 => 'PLL_ACCOUNTS_RELATED_RECORDS_IN_HIERARCHY'
		];
		if ($value) {
			return $data[$value];
		}
		return $data;
	}

	/**
	 * Function removes record
	 * @return boolean
	 */
	public function delete()
	{
		$db = \App\Db::getInstance('webservice');
		$recordId = $this->getId();
		if ($recordId) {
			$table = $this->getModule()->getBaseTable();
			$index = $this->getModule()->getTableIndex();
			$result = $db->createCommand()->delete($table, [$index => $recordId])->execute();
		}
		return !empty($result);
	}
}
