<?php
/**
 * Re-encrypt passwords from production key to current config/secret_keys.php.
 * Run: docker compose exec app php tests/reencrypt_passwords.php
 */
declare(strict_types=1);

chdir(dirname(__DIR__));
define('ROOT_DIRECTORY', getcwd());
require ROOT_DIRECTORY . '/vendor/autoload.php';

$oldKey = 'Maniek9111234567';
$method = 'aes-256-cbc';
$vector = 'Maniek9111234567';

$newKey = null;
require ROOT_DIRECTORY . '/config/secret_keys.php';
$newKey = $SECURITY_KEYS_CONFIG['encryptionPass'] ?? null;
if (!$newKey) {
	fwrite(STDERR, "Missing encryptionPass in config/secret_keys.php\n");
	exit(1);
}

$crypt = static function (string $value, string $key, bool $encrypt) use ($method, $vector): string {
	if ($value === '') {
		return '';
	}
	if ($encrypt) {
		$out = openssl_encrypt($value, $method, $key, 0, $vector);
		return $out === false ? '' : base64_encode($out);
	}
	$out = openssl_decrypt(base64_decode($value), $method, $key, 0, $vector);
	return $out === false ? '' : $out;
};

$db = new PDO(
	'mysql:host=db;dbname=freecrm;charset=utf8',
	'freecrm',
	'freecrm',
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$adminDb = new PDO(
	'mysql:host=db;dbname=freecrm;charset=utf8',
	'freecrm',
	'freecrm',
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = [
	['db' => $adminDb, 'table' => 's_yf_mail_smtp', 'id' => 'id', 'columns' => ['password']],
];

$updated = 0;
$failed = 0;

foreach ($tables as $spec) {
	$pdo = $spec['db'];
	$table = $spec['table'];
	$idCol = $spec['id'];
	$rows = $pdo->query("SELECT {$idCol}, " . implode(', ', $spec['columns']) . " FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rows as $row) {
		$sets = [];
		$params = [];
		foreach ($spec['columns'] as $col) {
			$encrypted = (string) ($row[$col] ?? '');
			if ($encrypted === '') {
				continue;
			}
			$plain = $crypt($encrypted, $oldKey, false);
			if ($plain === '') {
				fwrite(STDERR, "FAIL decrypt {$table}.{$col} id={$row[$idCol]}\n");
				$failed++;
				continue 2;
			}
			$reencrypted = $crypt($plain, $newKey, true);
			if ($reencrypted === '') {
				fwrite(STDERR, "FAIL encrypt {$table}.{$col} id={$row[$idCol]}\n");
				$failed++;
				continue 2;
			}
			$sets[] = "{$col} = ?";
			$params[] = $reencrypted;
		}
		if (!$sets) {
			continue;
		}
		$params[] = $row[$idCol];
		$pdo->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $idCol . ' = ?')->execute($params);
		echo "OK {$table} id={$row[$idCol]}\n";
		$updated++;
	}
}

echo "Done. Updated: {$updated}, failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
