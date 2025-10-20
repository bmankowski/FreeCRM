<?php

namespace App\Modules\KnowledgeBase\Models;

/**
 * Model of tree
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Tree extends \App\Runtime\Vtiger_Base_Model
{

	private $lastIdinTree;

	public function getModuleName()
	{
		return $this->get('moduleName');
	}

	public function getFolders()
	{
		$folders = [];
		$db = \App\Database\PearDatabase::getInstance();
		$lastId = 0;
		$result = $db->pquery('SELECT * FROM vtiger_trees_templates_data WHERE templateid = ?', [$this->getTemplate()]);
		while ($row = $db->getRow($result)) {
			$treeID = (int) ltrim($row['tree'], 'T');
			$pieces = explode('::', $row['parenttrre']);
			end($pieces);
			$parent = (int) ltrim(prev($pieces), 'T');
			$tree = [
				'id' => $treeID,
				'type' => 'folder',
				'record_id' => $row['tree'],
				'parent' => $parent == 0 ? '#' : $parent,
				'text' => \App\Runtime\Vtiger_Language_Handler::translate($row['name'], $this->getModuleName())
			];
			if (!empty($row['icon'])) {
				$tree['icon'] = $row['icon'];
			}
			$folders[] = $tree;
			if ($treeID > $lastId) {
				$lastId = $treeID;
			}
		}
		$this->lastIdinTree = $lastId;
		return $folders;
	}

	public function getTemplate()
	{
		return $this->getTreeField()['fieldparams'];
	}

	public function getTreeField()
	{
		if ($this->has('fieldTemp')) {
			return $this->get('fieldTemp');
		}
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT tablename,columnname,fieldname,fieldlabel,fieldparams FROM vtiger_field WHERE uitype = ? && tabid = ?', [302, \vtlib\Functions::getModuleId($this->getModuleName())]);
		$fieldTemp = $db->getRow($result);
		$this->set('fieldTemp', $fieldTemp);
		return $fieldTemp;
	}

	public function getAllRecords()
	{
		$queryGenerator = new \App\QueryGenerator($this->getModuleName());
		$queryGenerator->setFields(['id', 'category', 'knowledgebase_view', 'subject']);
		return $queryGenerator->createQuery()->all();
	}

	public function getDocuments()
	{
		$records = $this->getAllRecords();
		$fieldName = $this->getTreeField()['fieldname'];
		foreach ($records as &$item) {
			$this->lastIdinTree++;
			$parent = (int) ltrim($item[$fieldName], 'T');
			$tree[] = [
				'id' => $this->lastIdinTree,
				'type' => $item['knowledgebase_view'],
				'record_id' => $item['id'],
				'parent' => $parent == 0 ? '#' : $parent,
				'text' => $item['subject'],
				'icon' => 'glyphicon glyphicon-file'
			];
		};
		return $tree;
	}

	static public function getInstance($moduleModel)
	{
		$model = new self();
		$moduleName = $moduleModel->get('name');
		$model->set('module', $moduleModel)->set('moduleName', $moduleName);
		return $model;
	}
}
