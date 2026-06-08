<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class ImageResponse
{
	public static function serve(string $assetPath): void
	{
		if (!is_readable($assetPath)) {
			http_response_code(404);
			exit;
		}
		header('Content-Type: image/png');
		header('Cache-Control: no-store, no-cache');
		header('Pragma: no-cache');
		header('Content-Length: ' . (string) filesize($assetPath));
		readfile($assetPath);
		exit;
	}
}
