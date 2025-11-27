<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Persistence helper for import_mappings table.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

class MappingRepository
{
	private \yii\db\Connection $db;

	public function __construct(?\yii\db\Connection $connection = null)
	{
		$this->db = $connection ?? \App\Db\Db::getInstance();
	}

	public function save(MappingDefinition $definition): array
	{
		$now = date('Y-m-d H:i:s');
		$data = [
			'batch_id' => $definition->getBatchId(),
			'module' => $definition->getModuleName(),
			'mapping' => \App\Utils\Json::encode($definition->getMapping()),
			'default_values' => \App\Utils\Json::encode($definition->getDefaultValues()),
			'duplicate_sets' => \App\Utils\Json::encode($definition->getDuplicateSets()),
			'options' => \App\Utils\Json::encode($definition->getOptions()),
			'updated_at' => $now,
		];

		$existingId = (new \App\Db\Query())
			->select('id')
			->from('#__import_mappings')
			->where(['batch_id' => $definition->getBatchId()])
			->scalar($this->db);

		if ($existingId) {
			$this->db->createCommand()
				->update('#__import_mappings', $data, ['id' => $existingId])
				->execute();

			return [
				'id' => (int) $existingId,
				'action' => 'updated',
			];
		}

		$data['created_at'] = $now;
		$this->db->createCommand()
			->insert('#__import_mappings', $data)
			->execute();

		return [
			'id' => (int) $this->db->getLastInsertID(),
			'action' => 'created',
		];
	}
}

