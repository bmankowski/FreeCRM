<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * One-off CLI tool: reset any user's password to a brand-new Argon2id hash.
 *
 * Use this to recover accounts after the legacy MD5-crypt password migration
 * (every existing $1$... hash is invalidated by design) or after a deliberate
 * pepper rotation.
 *
 * Usage:
 *   php tests/reset_user_password.php <user_name> <new_password>
 *
 * The script:
 *   - Boots the FreeCRM framework so $user_password_pepper and the Argon2id
 *     parameters are loaded exactly the same way as the web stack.
 *   - Hashes <new_password> via \App\Security\PasswordCrypto.
 *   - UPDATEs vtiger_users.user_password directly. Does not touch confirm_password
 *     or crypt_type (those columns are removed by the schema migration).
 *
 * Safety: refuses to run unless invoked from the CLI SAPI. No web exposure.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	echo "tests/reset_user_password.php is a CLI tool only.\n";
	exit(1);
}

if ($argc < 3) {
	$progname = $argv[0] ?? 'reset_user_password.php';
	fwrite(STDERR, "Usage: php {$progname} <user_name> <new_password>\n");
	exit(2);
}

$userName = (string) $argv[1];
$newPassword = (string) $argv[2];

if ($userName === '' || $newPassword === '') {
	fwrite(STDERR, "user_name and new_password must both be non-empty.\n");
	exit(2);
}

chdir(__DIR__ . '/../');
define('REQUEST_MODE', 'Cli');
define('ROOT_DIRECTORY', getcwd() !== false ? getcwd() : __DIR__);

require_once ROOT_DIRECTORY . '/vendor/autoload.php';
require_once ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require_once ROOT_DIRECTORY . '/config/api.php';
require_once ROOT_DIRECTORY . '/config/config.php';

\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();

try {
	$row = (new \App\Db\Query())
		->select(['id', 'deleted', 'status'])
		->from('vtiger_users')
		->where(['user_name' => $userName])
		->one();

	if (!$row) {
		fwrite(STDERR, "User '{$userName}' not found.\n");
		exit(3);
	}
	if ((int) $row['deleted'] !== 0) {
		fwrite(STDERR, "User '{$userName}' is marked deleted; refusing to reset.\n");
		exit(3);
	}

	$hash = \App\Security\PasswordCrypto::hash($newPassword);

	$affected = \App\Db\Db::getInstance()->createCommand()
		->update('vtiger_users', ['user_password' => $hash], ['id' => (int) $row['id']])
		->execute();

	if ($affected < 1) {
		fwrite(STDERR, "UPDATE returned 0 rows; password not changed.\n");
		exit(4);
	}

	fwrite(STDOUT, "Password for '{$userName}' reset successfully.\n");
	exit(0);
} catch (\Throwable $e) {
	fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
	exit(1);
}
