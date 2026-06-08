<?php
/**
 * Success page after a valid unsubscribe LinkAction token.
 *
 * Included by Response::render('unsubscribe_ok'); language from ?lang= or Accept-Language (pl/en).
 */
declare(strict_types=1);

$lang = strtolower(substr((string) ($_GET['lang'] ?? ''), 0, 2));
if ($lang !== 'pl' && $lang !== 'en') {
	$accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
	$lang = str_starts_with($accept, 'pl') ? 'pl' : 'en';
}

if ($lang === 'pl') {
	$title = 'Wypisanie zakończone';
	$message = 'Twoja prośba o wypisanie z dalszego kontaktu marketingowego została przyjęta.';
	$hint = 'Zmiana zostanie uwzględniona w naszym systemie w ciągu kilku minut.';
} else {
	$title = 'Unsubscribe completed';
	$message = 'Your request to unsubscribe from future marketing contact has been accepted.';
	$hint = 'The change will be applied in our system within a few minutes.';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
	<style>
		body { font-family: Lato, Arial, sans-serif; background: #f5f5f8; color: #333; margin: 0; padding: 2rem; }
		.card { max-width: 560px; margin: 4rem auto; background: #fff; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
		h1 { font-size: 1.5rem; margin: 0 0 1rem; color: #8B7AB5; }
		p { line-height: 1.5; margin: 0 0 1rem; }
	</style>
</head>
<body>
	<div class="card">
		<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
		<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
		<p><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
	</div>
</body>
</html>
