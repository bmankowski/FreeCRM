<?php
/**
 * Generic failure page for rejected or invalid /la links.
 *
 * Included by Response::render('error'); language from ?lang= or Accept-Language (pl/en).
 */
declare(strict_types=1);

$lang = strtolower(substr((string) ($_GET['lang'] ?? ''), 0, 2));
if ($lang !== 'pl' && $lang !== 'en') {
	$accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
	$lang = str_starts_with($accept, 'pl') ? 'pl' : 'en';
}

if ($lang === 'pl') {
	$title = 'Nie udało się przetworzyć linku';
	$message = 'Ten link jest nieprawidłowy lub wygasł. Jeśli nadal chcesz wypisać się z kontaktu marketingowego, skontaktuj się z nami bezpośrednio.';
} else {
	$title = 'Unable to process link';
	$message = 'This link is invalid or has expired. If you still wish to unsubscribe from marketing contact, please contact us directly.';
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
		h1 { font-size: 1.5rem; margin: 0 0 1rem; color: #666; }
		p { line-height: 1.5; margin: 0; }
	</style>
</head>
<body>
	<div class="card">
		<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
		<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
	</div>
</body>
</html>
