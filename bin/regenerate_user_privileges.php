#!/usr/bin/env php
<?php
/**
 * FreeCRM - Regenerate all user_privileges cache files from DB and bundled defaults.
 *
 * Run: docker compose exec -T app php bin/regenerate_user_privileges.php
 */

declare(strict_types=1);

if (!defined('ROOT_DIRECTORY')) {
	define('ROOT_DIRECTORY', dirname(__DIR__));
}

require ROOT_DIRECTORY . '/vendor/autoload.php';
require ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require ROOT_DIRECTORY . '/config/api.php';
require ROOT_DIRECTORY . '/config/config.php';

\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();
\App\Cache\Cache::init();
\App\Db\Db::$connectCache = \App\Core\AppConfig::performance('ENABLE_CACHING_DB_CONNECTION');

$privDir = ROOT_DIRECTORY . '/user_privileges';
if (!is_dir($privDir)) {
	mkdir($privDir, 0755, true);
}

/**
 * @param array<string, string> $defaults filename => file body
 */
function ensurePrivilegeFile(string $dir, string $filename, string $body): string
{
	$path = $dir . '/' . $filename;
	if (is_file($path)) {
		return 'kept';
	}
	file_put_contents($path, $body);
	return 'created';
}

echo "Regenerating user_privileges cache files...\n";

try {
	\App\Utils\VtlibUtils::recreateUserPrivilegeFiles();
	echo "  tabdata.php + user_privileges_*.php + sharing_privileges_*.php\n";
} catch (\Throwable $e) {
	fwrite(STDERR, "ERROR: recreateUserPrivilegeFiles failed: {$e->getMessage()}\n");
	exit(1);
}

$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
$menuRecordModel->refreshMenuFiles();
$menuFiles = glob($privDir . '/menu_*.php') ?: [];
echo sprintf("  menu_*.php (%d file(s))\n", count($menuFiles));

\App\Security\PrivilegeAdvanced::reloadCache();
echo "  advancedPermission.php\n";

if (\App\Core\AppConfig::module('ModTracker', 'WATCHDOG')) {
	\App\Modules\Base\Models\Watchdog::reloadCache();
	echo "  watchdogModule.php\n";
} else {
	echo "  watchdogModule.php (skipped — ModTracker WATCHDOG disabled)\n";
}

\App\Security\PrivilegeFile::createUsersFile();
echo "  users.php\n";

$locksStatus = ensurePrivilegeFile($privDir, 'locks.php', "<?php\n\$locksRaw = [];\n\$locks = [];\n");
echo "  locks.php ($locksStatus)\n";

$switchUsersStatus = ensurePrivilegeFile(
	$privDir,
	'switchUsers.php',
	"<?php\n\$switchUsersRaw = [];\n\$switchUsers = [];\n"
);
try {
	(new \App\Modules\Settings\Users\Models\Module())->refreshSwitchUsers();
	echo "  switchUsers.php ($switchUsersStatus, refreshed)\n";
} catch (\Throwable $e) {
	echo "  switchUsers.php ($switchUsersStatus, refresh skipped: {$e->getMessage()})\n";
}

foreach (['module_record_allocation.php', 'sharedOwner.php'] as $allocationFile) {
	$status = ensurePrivilegeFile($privDir, $allocationFile, "<?php\n\$map = [];\n");
	echo "  $allocationFile ($status)\n";
}

$moduleHierarchyPath = $privDir . '/moduleHierarchy.php';
if (is_file($moduleHierarchyPath)) {
	echo "  moduleHierarchy.php (kept)\n";
} else {
	$source = ROOT_DIRECTORY . '/config/moduleHierarchy.php';
	if (!is_file($source)) {
		fwrite(STDERR, "ERROR: config/moduleHierarchy.php missing — cannot restore moduleHierarchy.php\n");
		exit(1);
	}
	$content = '<?php' . PHP_EOL . 'return ' . \vtlib\Functions::varExportMin(require $source) . ';' . PHP_EOL;
	file_put_contents($moduleHierarchyPath, $content);
	echo "  moduleHierarchy.php (created from config/moduleHierarchy.php)\n";
}

$required = [
	'tabdata.php',
	'menu_0.php',
	'locks.php',
	'users.php',
	'advancedPermission.php',
	'moduleHierarchy.php',
	'switchUsers.php',
	'module_record_allocation.php',
	'sharedOwner.php',
	'user_privileges_1.php',
];
if (\App\Core\AppConfig::module('ModTracker', 'WATCHDOG')) {
	$required[] = 'watchdogModule.php';
}

$errors = [];
foreach ($required as $file) {
	$path = $privDir . '/' . $file;
	if (!is_file($path)) {
		$errors[] = "$file missing";
		continue;
	}
	if (filesize($path) === 0) {
		$errors[] = "$file is empty";
	}
}

$userFiles = glob($privDir . '/user_privileges_*.php') ?: [];
$sharingFiles = glob($privDir . '/sharing_privileges_*.php') ?: [];
echo sprintf(
	"\nSummary: %d menu, %d user, %d sharing file(s)\n",
	count($menuFiles),
	count($userFiles),
	count($sharingFiles)
);

if ($errors !== []) {
	foreach ($errors as $error) {
		fwrite(STDERR, "ERROR: $error\n");
	}
	exit(1);
}

echo "OK — all required privilege cache files present.\n";
exit(0);
