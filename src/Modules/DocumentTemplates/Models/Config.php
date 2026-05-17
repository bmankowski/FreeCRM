<?php

namespace App\Modules\DocumentTemplates\Models;

/**
 * PDF configuration model.
 *
 * @package FreeCRM.Settings.Template
 * @license FreeCRM Public License 1.1
 * @author bmankowski@gmail.com
 */
class Config
{
	const TABLE_NAME = 'u_yf_documenttemplates_config';
	const GENERATOR_FOOTER_TEMPLATE = 'generator_footer_template';

	protected static $cache = [];

	public static function getGeneratorFooterTemplate()
	{
		return static::get(self::GENERATOR_FOOTER_TEMPLATE, static::getDefaultGeneratorFooterTemplate());
	}

	public static function setGeneratorFooterTemplate($value)
	{
		static::set(self::GENERATOR_FOOTER_TEMPLATE, (string) $value);
	}

	public static function getDefaultGeneratorFooterTemplate()
	{
		return '<div class="text-muted small text-right">$(generator : full_name)$ | $(general : CurrentDate)$</div>';
	}

	protected static function get($name, $default = '')
	{
		if (array_key_exists($name, static::$cache)) {
			return static::$cache[$name];
		}
		static::ensureTable();
		$row = (new \App\Db\Query())
			->select(['value'])
			->from(self::TABLE_NAME)
			->where(['param' => $name])
			->one(\App\Db\Db::getInstance('admin'));
		if ($row === false) {
			static::$cache[$name] = $default;
			return $default;
		}
		static::$cache[$name] = $row['value'];
		return static::$cache[$name];
	}

	protected static function set($name, $value)
	{
		static::ensureTable();
		$db = \App\Db\Db::getInstance('admin');
		$exists = (new \App\Db\Query())
			->from(self::TABLE_NAME)
			->where(['param' => $name])
			->exists($db);
		if ($exists) {
			$db->createCommand()->update(self::TABLE_NAME, ['value' => $value], ['param' => $name])->execute();
		} else {
			$db->createCommand()->insert(self::TABLE_NAME, ['param' => $name, 'value' => $value])->execute();
		}
		static::$cache[$name] = $value;
	}

	protected static function ensureTable()
	{
		$db = \App\Db\Db::getInstance('admin');
		$tableName = $db->quoteSql(self::TABLE_NAME);
		if ($db->isTableExists($tableName)) {
			return;
		}
		$db->createCommand(
			'CREATE TABLE IF NOT EXISTS ' . $db->quoteTableName($tableName) . ' (
				`param` varchar(64) NOT NULL,
				`value` mediumtext NOT NULL,
				PRIMARY KEY (`param`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8'
		)->execute();
		$db->getSchema()->refreshTableSchema($tableName);
	}
}
