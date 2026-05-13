<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Adds layout_header/layout_body/layout_footer to PDF dynamic elements for
 * PLL_DOCUMENT_LAYOUT records; migrates legacy content column into layout_body.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260513_000001_pdf_dynamic_elements_layout_parts extends Migration
{
	private const TABLE = 'a_yf_pdf_dynamic_elements';

	private const DOCUMENT_LAYOUT = 'PLL_DOCUMENT_LAYOUT';

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		$mediumText = $this->mediumText()->notNull()->defaultValue('');

		if (!isset($schema->columns['layout_header'])) {
			$this->addColumn(self::TABLE, 'layout_header', $mediumText);
		}
		if (!isset($schema->columns['layout_body'])) {
			$this->addColumn(self::TABLE, 'layout_body', $mediumText);
		}
		if (!isset($schema->columns['layout_footer'])) {
			$this->addColumn(self::TABLE, 'layout_footer', $mediumText);
		}

		// Legacy installs stored PLL_DOCUMENT_LAYOUT HTML in `content`; copy once into layout_body.
		$this->db->createCommand(
			'UPDATE `' . self::TABLE . '` SET `layout_body` = `content`, `content` = \'\''
				. ' WHERE `type` = :type AND `content` <> \'\''
				. ' AND (`layout_body` = \'\' OR `layout_body` IS NULL)',
			[':type' => self::DOCUMENT_LAYOUT]
		)->execute();
	}

	public function safeDown(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		$this->db->createCommand(
			'UPDATE `' . self::TABLE . '` SET `content` = `layout_body` WHERE `type` = :type AND `content` = \'\'',
			[':type' => self::DOCUMENT_LAYOUT]
		)->execute();

		if (isset($schema->columns['layout_footer'])) {
			$this->dropColumn(self::TABLE, 'layout_footer');
		}
		if (isset($schema->columns['layout_body'])) {
			$this->dropColumn(self::TABLE, 'layout_body');
		}
		if (isset($schema->columns['layout_header'])) {
			$this->dropColumn(self::TABLE, 'layout_header');
		}
	}
}
