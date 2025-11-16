<?php

namespace App\Modules\Base\Models;

/**
 * Basic TreeCategoryModal Model Class
 * @package YetiForce.TreeCategoryModal
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class TreeCategoryModal extends \App\Runtime\BaseModel
{

    /** @var int|string|null */
    protected $lastIdinTree;


	static $_cached_instance;

	/**
	 * Function to get the Module Name
	 * @return string Module name
	 */
	public function getModuleName()
	{
		return $this->get('moduleName');
	}

	/**
	 * Load tree ID
	 * @return type
	 */
	public function getTemplate()
	{
		return $this->getTreeField()['fieldparams'];
	}

	/**
	 * Load tree field info
	 * @return array
	 */
	public function getTreeField()
	{
		if ($this->has('fieldTemp')) {
			return $this->get('fieldTemp');
		}
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT tablename,columnname,fieldname,fieldlabel,fieldparams FROM vtiger_field WHERE uitype = ? AND tabid = ?', [302, \vtlib\Functions:: getModuleId($this->getModuleName())]);
		$fieldTemp = $db->getRow($result);
		$this->set('fieldTemp', $fieldTemp);
		return $fieldTemp;
	}

	/**
	 * Static Function to get the instance of Vtiger TreeView Model for the given Vtiger Module Model
	 * @param string name of the module
	 * @return \App\Modules\Base\Models\TreeView instance
	 */
	public static function getInstance(\App\Modules\Base\Models\Module $moduleModel)
	{
		$moduleName = $moduleModel->get('name');
		if (isset(self::$_cached_instance[$moduleName])) {
			return self::$_cached_instance[$moduleName];
		}
		$modelClassName = \App\Core\Loader::getComponentClassName('Model', 'TreeCategoryModal', $moduleName);
		$instance = new $modelClassName();
		$instance->set('module', $moduleModel)->set('moduleName', $moduleName)->set('moduleName', $moduleName);
		self::$_cached_instance[$moduleName] = $instance;
		return self::$_cached_instance[$moduleName];
	}

	public function getRelationType()
	{
		if ($this->has('relationType')) {
			return $this->get('relationType');
		}
		$srcModuleModel = \App\Modules\Base\Models\Module::getInstance($this->get('srcModule'));
		$relationModel = \App\Modules\Base\Models\Relation::getInstance($srcModuleModel, $this->get('module'));
		$this->set('relationType', $relationModel->getRelationType());
		return $this->get('relationType');
	}

	public function isDeletable()
	{
		$srcModuleModel = \App\Modules\Base\Models\Module::getInstance($this->get('srcModule'));
		$relationModel = \App\Modules\Base\Models\Relation::getInstance($srcModuleModel, $this->get('module'));
		return $relationModel->isDeletable();
	}

	public function getTreeData()
	{
		return array_merge($this->getTreeList(), $this->getRecords());
	}

	/**
	 * Load tree
	 * @return String
	 */
	private function getTreeList()
	{
		$trees = [];
		$db = \App\Database\PearDatabase::getInstance();
		$isDeletable = $this->isDeletable();
		$lastId = 0;
		$result = $db->pquery('SELECT * FROM vtiger_trees_templates_data WHERE templateid = ?', [$this->getTemplate()]);
		$selected = $this->getSelectedTreeList();
		while ($row = $db->getRow($result)) {
			$treeID = (int) ltrim($row['tree'], 'T');
			$pieces = explode('::', $row['parenttrre']);
			end($pieces);
			$parent = (int) ltrim(prev($pieces), 'T');
			$tree = [
				'id' => $treeID,
				'type' => 'category',
				'record_id' => $row['tree'],
				'parent' => $parent == 0 ? '#' : $parent,
				'text' => \App\Runtime\Vtiger_Language_Handler::translate($row['name'], $this->getModuleName())
			];
			if (!empty($row['icon'])) {
				$tree['icon'] = $row['icon'];
			}
			$checked = in_array($row['tree'], $selected);
			if ($checked) {
				$tree['category'] = ['checked' => true];
			}
			if (!$isDeletable && $checked) {
				$tree['state']['disabled'] = true;
			}
			$trees[] = $tree;
			if ($treeID > $lastId) {
				$lastId = $treeID;
			}
		}
		$this->lastIdinTree = $lastId;
		return $trees;
	}

	private function getSelectedTreeList()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT tree FROM u_yf_crmentity_rel_tree WHERE crmid = ? AND relmodule = ?', [$this->get('srcRecord'), $this->get('module')->getId()]);
		return $db->getArrayColumn($result);
	}

	private function getSelectedRecords($onlyKeys = true)
	{
		$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($this->get('srcRecord'), $this->get('srcModule'));
		$relationListView = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $this->getModuleName());
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('limit', 'no_limit');
		$entries = $relationListView->getEntries($pagingModel);
		if ($onlyKeys) {
			return array_keys($entries);
		} else {
			return $entries;
		}
	}

	private function getAllRecords()
	{

		$listViewModel = \App\Modules\Base\Models\ListView::getInstanceForPopup($this->getModuleName(), $this->get('srcModule'));
		if (!empty($this->get('srcModule'))) {
			$listViewModel->set('src_module', $this->get('srcModule'));
			$listViewModel->set('src_record', $this->get('srcRecord'));
		}
		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('limit', 'no_limit');
		$listViewModel->get('query_generator')->setField($this->getTreeField()['fieldname']);
		$listEntries = $listViewModel->getListViewEntries($pagingModel);
		return $listEntries;
	}

	private function getRecords()
	{
		$selectedRecords = $this->getSelectedRecords();
		$isDeletable = $this->isDeletable();
		if ($this->getRelationType() == 2) {
			$listEntries = $this->getAllRecords();
		} else {
			$listEntries = $this->getSelectedRecords(false);
		}

		$fieldName = $this->getTreeField()['fieldname'];
		$tree = [];
		foreach ($listEntries as $item) {
			$this->lastIdinTree++;
			$parent = (int) ltrim($item->get($fieldName), 'T');
			$selected = in_array($item->getId(), $selectedRecords);
			$state = ['selected' => $selected];
			if (!$isDeletable && $selected) {
				$state['disabled'] = true;
			}
			$tree[] = [
				'id' => $this->lastIdinTree,
				'type' => 'record',
				'record_id' => $item->getId(),
				'parent' => $parent == 0 ? '#' : $parent,
				'text' => $item->getName(),
				'state' => $state,
				'icon' => 'glyphicon glyphicon-file'
			];
		}
		return $tree;
	}
}
