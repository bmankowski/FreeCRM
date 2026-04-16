<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Cron\Tasks;

final class MultiReferenceTask extends AbstractCronTask
{
	public function execute(): void
	{
		$db = \App\Db\Db::getInstance();
		$executed = [];
		$limit = 1000;
		$rows = (new \App\Db\Query())->from('s_#__multireference')->all();

		foreach ($rows as &$multireference) {
			if ((int) $multireference['type'] === 0) {
				$queryGenerator = new \App\QueryField\QueryGenerator($multireference['source_module']);
				$queryGenerator->setFields(['id']);
				$queryGenerator->addCondition('id', $multireference['lastid'], 'a');
				$queryGenerator->setOrder('id', 'ASC');

				$fields = \App\Modules\Base\UiTypes\MultiReferenceValue::getFieldsByModules($multireference['source_module'], $multireference['dest_module']);
				$dataReader = $queryGenerator->createQuery()->limit($limit)->createCommand()->query();
				unset($queryGenerator);
				$queryGenerator = new \App\QueryField\QueryGenerator($multireference['source_module']);
				$queryGenerator->setFields(['id']);
				$queryGenerator->addCondition('id', $multireference['lastid'], 'a');
				$queryGenerator->setOrder('id', 'DESC');
				$lastId = $queryGenerator->createQuery()->limit(1)->scalar();
				unset($queryGenerator);

				while ($id = $dataReader->readColumn(0)) {
					foreach ($fields as &$field) {
						$fieldModel = new \App\Modules\Base\Models\Field();
						$fieldModel->initialize($field);
						$UITypeModel = $fieldModel->getUITypeModel();
						$UITypeModel->reloadValue($multireference['source_module'], $id);
					}
					if ($lastId === $id) {
						$db->createCommand()->delete('s_#__multireference', [
							'source_module' => $multireference['source_module'],
							'dest_module' => $multireference['dest_module'],
							'type' => 0
						])->execute();
					} else {
						$db->createCommand()
							->update('s_#__multireference', [
								'lastid' => $id,
							], ['source_module' => $multireference['source_module'], 'dest_module' => $multireference['dest_module'], 'type' => 0])
							->execute();
					}
				}
			} else {
				if (\App\Records\Record::isExists($multireference['lastid'], $multireference['source_module'])) {
					if (in_array($multireference['lastid'], $executed)) {
						continue;
					}
					$fields = \App\Modules\Base\UiTypes\MultiReferenceValue::getFieldsByModules($multireference['source_module'], $multireference['dest_module']);
					foreach ($fields as $field) {
						$fieldModel = new \App\Modules\Base\Models\Field();
						$fieldModel->initialize($field);
						$UITypeModel = $fieldModel->getUITypeModel();
						$UITypeModel->reloadValue($multireference['source_module'], $multireference['lastid']);
						$executed[] = $multireference['lastid'];
					}
				}
				$db->createCommand()->delete('s_#__multireference', [
					'source_module' => $multireference['source_module'],
					'dest_module' => $multireference['dest_module'],
					'lastid' => $multireference['lastid'],
					'type' => $multireference['type']
				])->execute();
			}
		}
	}
}
