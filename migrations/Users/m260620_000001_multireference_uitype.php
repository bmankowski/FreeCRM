<?php
/**
 * FreeCRM - Register MultiReference field type (uitype 306) in webservice metadata.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260620_000001_multireference_uitype extends Migration
{
	private const UITYPE = 306;
	private const WS_FIELDTYPE_ID = 58;

	public function safeUp(): void
	{
		if (!(new Query())->from('vtiger_ws_fieldtype')->where(['uitype' => self::UITYPE])->exists()) {
			$this->insert('vtiger_ws_fieldtype', [
				'fieldtypeid' => self::WS_FIELDTYPE_ID,
				'uitype' => self::UITYPE,
				'fieldtype' => 'multiReference',
			]);
		}
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_ws_fieldtype', ['uitype' => self::UITYPE]);
	}
}
