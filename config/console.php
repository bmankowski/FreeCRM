<?php
declare(strict_types=1);

use App\Db\Db;

$dbConfig = Db::getConfig('base');

return [
	'id' => 'freecrm-console',
	'name' => 'FreeCRM Console',
	'basePath' => ROOT_DIRECTORY,
	'controllerNamespace' => 'yii\console\controllers',
	'runtimePath' => ROOT_DIRECTORY . '/cache/runtime/console',
	'controllerMap' => [
		'migrate' => [
			'class' => \yii\console\controllers\MigrateController::class,
			'migrationTable' => 'yf_migration',
		],
	],
	'bootstrap' => [],
	'components' => [
		'db' => array_merge([
			'class' => Db::class,
		], $dbConfig),
		'log' => [
			'targets' => [
				[
					'class' => \yii\log\FileTarget::class,
					'levels' => ['error', 'warning'],
					'logFile' => ROOT_DIRECTORY . '/cache/logs/console.log',
				],
			],
		],
	],
];

