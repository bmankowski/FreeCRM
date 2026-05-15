<?php
/**
 * Test SMTP configuration (decrypt + send).
 * Run: docker compose exec app php tests/test_smtp_send.php [smtp_id]
 */
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_USER_AGENT'] = 'FreeCRM-SMTP-Test';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_PORT'] = '80';

chdir(dirname(__DIR__));
define('ROOT_DIRECTORY', getcwd());
define('REQUEST_MODE', 'WebUI');

require ROOT_DIRECTORY . '/vendor/autoload.php';
require ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require ROOT_DIRECTORY . '/config/api.php';
require ROOT_DIRECTORY . '/config/config.php';
\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();
\App\EntryPoint\WebUI::initialize();

$smtpId = isset($argv[1]) ? (int) $argv[1] : 1;

$encryption = new \App\Security\Encryption();
echo 'Encryption active: ' . ($encryption->isActive() ? 'yes' : 'no') . PHP_EOL;

\App\Cache\Cache::delete('SmtpServers', 'all');
\App\Cache\Cache::delete('SmtpServer', $smtpId);

$smtp = \App\Email\Mail::getSmtpById($smtpId);
if (!$smtp) {
	fwrite(STDERR, "SMTP id={$smtpId} not found\n");
	exit(1);
}
echo "SMTP: {$smtp['name']} ({$smtp['host']}:{$smtp['port']}, secure={$smtp['secure']}) from={$smtp['from_email']}" . PHP_EOL;

$plain = $encryption->isActive() ? $encryption->decrypt($smtp['password']) : $smtp['password'];
echo 'Password decrypt: ' . ($plain !== '' && $plain !== $smtp['password'] ? 'OK' : 'FAIL') . PHP_EOL;

$user = \App\Modules\Users\Models\Record::getInstanceById(1, 'Users');
$to = $user && !$user->isEmpty('email1') ? $user->get('email1') : 'test@test.pl';
echo "Recipient: {$to}" . PHP_EOL;

$mailer = new \App\Email\Mailer();
$mailer->loadSmtpByID($smtpId);
$mailer->subject('FreeCRM SMTP test ' . date('Y-m-d H:i:s'));
$mailer->content('<p>Test wysyłki SMTP z FreeCRM (CLI).</p>');
$mailer->to($to);

if ($mailer->send()) {
	echo "SMTP send: SUCCESS\n";
	exit(0);
}

echo "SMTP send: FAILED\n";
$log = @file_get_contents(ROOT_DIRECTORY . '/cache/logs/system.log');
if ($log && preg_match_all('/\[Mailer\].*$/m', $log, $m)) {
	echo implode(PHP_EOL, array_slice($m[0], -5)) . PHP_EOL;
}
exit(1);
