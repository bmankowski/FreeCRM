<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 *
 * CLI: import Verama jobs from import/jobs/pending.
 *   docker compose exec -T cron gosu www-data php src/Modules/ProjektyRekrutacyjne/scripts/importVeramaJobs.php
 */

declare(strict_types=1);

$rootDirectory = dirname(__DIR__, 4);
chdir($rootDirectory);

require_once $rootDirectory . '/vendor/autoload.php';
\App\Modules\Cron\Bootstrap::init();

$automatId = \App\Modules\Users\Models\Record::getUserIdByName('automat');
\App\Modules\Users\Models\Record::setCurrentUserId($automatId);

try {
	$stats = (new \App\Modules\ProjektyRekrutacyjne\Services\Verama\VeramaJobImporter())->importFromPending();
	echo sprintf(
		"Verama import OK: created=%d updated=%d closed=%d processed=%d\n",
		$stats['created'],
		$stats['updated'],
		$stats['closed'],
		$stats['processed']
	);
	exit(0);
} catch (\Throwable $e) {
	fwrite(STDERR, 'Verama import failed: ' . $e->getMessage() . "\n");
	fwrite(STDERR, $e->getTraceAsString() . "\n");
	exit(1);
}
