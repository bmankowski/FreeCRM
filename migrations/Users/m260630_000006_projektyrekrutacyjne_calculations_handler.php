<?php
/**
 * FreeCRM - Register ProjektyRekrutacyjne Calculations EntityBeforeSave handler.
 *
 * The handler regenerates job_advertisement_links on save. It was mapped in
 * EventHandler::$classNameMap but never inserted into vtiger_eventhandlers,
 * so it never fired.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260630_000006_projektyrekrutacyjne_calculations_handler extends Migration
{
	private const EVENT_NAME = 'EntityBeforeSave';
	private const HANDLER_CLASS = 'ProjektyRekrutacyjne_Calculations_Handler';
	private const INCLUDE_MODULES = 'ProjektyRekrutacyjne';

	public function safeUp(): void
	{
		\App\Events\EventHandler::registerHandler(
			self::EVENT_NAME,
			self::HANDLER_CLASS,
			self::INCLUDE_MODULES
		);
	}

	public function safeDown(): void
	{
		\App\Events\EventHandler::deleteHandler(self::HANDLER_CLASS, self::EVENT_NAME);
	}
}
