<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Repository encapsulating persistence logic for import_batches table.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

class BatchRepository
{
	private \yii\db\Connection $db;

	public function __construct(?\yii\db\Connection $connection = null)
	{
		$this->db = $connection ?? \App\Db\Db::getInstance();
	}

	public function create(array $data): int
	{
		$data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
		$this->db->createCommand()->insert('#__import_batches', $data)->execute();
		return (int) $this->db->getLastInsertID();
	}

	public function update(int $batchId, array $data): void
	{
		$data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
		$this->db->createCommand()->update('#__import_batches', $data, ['id' => $batchId])->execute();
	}

	public function attachPreviewStats(int $batchId, int $rows): void
	{
		$this->update($batchId, [
			'preview_rows' => $rows,
		]);
	}

	public function find(int $batchId): ?array
	{
		$row = (new \App\Db\Query())
			->from('#__import_batches')
			->where(['id' => $batchId])
			->limit(1)
			->one($this->db);

		return $row !== false ? $row : null;
	}

	public function delete(int $batchId): void
	{
		$this->db->createCommand()->delete('#__import_batches', ['id' => $batchId])->execute();
	}
}

