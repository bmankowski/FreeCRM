<?php
namespace App\Core;

use App\Cache\Cache;

use App\AppConfig;

/**
 * Company basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Company extends \App\Runtime\BaseModel
{

	/** @var string Web path to logos (relative to public document root) */
	public static $logoURL = 'storage/Logo/';

	/** @var string Filesystem path to logos (relative to ROOT_DIRECTORY) */
	public static $logoStoragePath = 'public/storage/Logo/';

	/**
	 * Absolute filesystem directory for company logos (under public/, served by the web server).
	 */
	public static function getLogoFilesystemDir(): string
	{
		return ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, static::$logoStoragePath);
	}

	public static function getLogoFilesystemPath(string $fileName): string
	{
		return static::getLogoFilesystemDir() . $fileName;
	}

	public static function getLogoWebPath(string $fileName): string
	{
		return static::$logoURL . $fileName;
	}

	public static function getLogoBase64SidecarSuffix(): string
	{
		return '.base64';
	}

	public static function getLogoBase64SidecarPath(string $fileName): string
	{
		return static::getLogoFilesystemPath($fileName) . static::getLogoBase64SidecarSuffix();
	}

	/**
	 * Write data URI sidecar (image.png.base64) for template/PDF inline embedding.
	 * WebP sources are stored as transparent PNG so email clients embed image/png (not image/webp).
	 */
	public static function writeLogoBase64Sidecar(string $fileName, string $mimeType = ''): bool
	{
		$full = static::getLogoFilesystemPath($fileName);
		if (!is_file($full) || !is_readable($full)) {
			return false;
		}
		$raw = @file_get_contents($full);
		if ($raw === false || $raw === '') {
			return false;
		}
		if ($mimeType === '' || strpos($mimeType, '/') === false) {
			try {
				$mimeType = \App\Fields\File::loadFromPath($full)->getMimeType();
			} catch (\Throwable $e) {
				$mimeType = 'application/octet-stream';
			}
		}
		$embedded = static::embeddedImageBinaryForDataUri($raw, $mimeType, $full, $fileName);
		if ($embedded === null) {
			return false;
		}
		$dataUri = 'data:' . $embedded['mime'] . ';base64,' . base64_encode($embedded['binary']);
		return false !== @file_put_contents(static::getLogoBase64SidecarPath($fileName), $dataUri, LOCK_EX);
	}

	/**
	 * Prepare image bytes for inline email/PDF data URIs (WebP → PNG with alpha).
	 *
	 * @return array{mime: string, binary: string}|null
	 */
	public static function embeddedImageBinaryForDataUri(string $raw, string $mimeType, string $sourcePath = '', string $fileName = ''): ?array
	{
		if ($fileName === '' && $sourcePath !== '') {
			$fileName = basename($sourcePath);
		}
		return static::buildLogoEmbeddedBinary($raw, $mimeType, $fileName, $sourcePath);
	}

	/**
	 * Prepare logo bytes for data-URI sidecars (WebP → PNG with alpha for email compatibility).
	 *
	 * @return array{mime: string, binary: string}|null
	 */
	protected static function buildLogoEmbeddedBinary(string $raw, string $mimeType, string $fileName, string $sourcePath = ''): ?array
	{
		if ($mimeType === 'image/webp' || preg_match('/\.webp$/i', $fileName)) {
			$png = static::convertImageBinaryToPngWithAlpha($raw, $sourcePath);
			if ($png !== null) {
				return ['mime' => 'image/png', 'binary' => $png];
			}
			return null;
		}
		return ['mime' => $mimeType, 'binary' => $raw];
	}

	/**
	 * Decode image bytes to PNG preserving the alpha channel (GD, then ImageMagick when GD lacks WebP).
	 */
	protected static function convertImageBinaryToPngWithAlpha(string $raw, string $sourcePath = ''): ?string
	{
		if (function_exists('imagecreatefromstring') && function_exists('imagepng')) {
			$image = @imagecreatefromstring($raw);
			if ($image === false && $sourcePath !== '' && function_exists('imagecreatefromwebp')) {
				$image = @imagecreatefromwebp($sourcePath);
			}
			if ($image !== false) {
				imagealphablending($image, false);
				imagesavealpha($image, true);
				ob_start();
				$ok = imagepng($image);
				$png = ob_get_clean();
				imagedestroy($image);
				if ($ok && $png !== false && $png !== '') {
					return $png;
				}
			}
		}
		if ($sourcePath !== '' && is_file($sourcePath)) {
			$png = static::convertImagePathToPngViaMagick($sourcePath);
			if ($png !== null) {
				return $png;
			}
		}
		return static::convertWebpBytesToPngViaMagick($raw);
	}

	/**
	 * Convert a logo file to PNG using ImageMagick (used when PHP GD has no WebP support).
	 */
	protected static function convertImagePathToPngViaMagick(string $path): ?string
	{
		$convert = static::findImageMagickConvertBinary();
		if ($convert === null) {
			return null;
		}
		$pngPath = tempnam(sys_get_temp_dir(), 'fc_logo_');
		if ($pngPath === false) {
			return null;
		}
		$pngOut = $pngPath . '.png';
		@unlink($pngPath);
		$cmd = escapeshellarg($convert) . ' ' . escapeshellarg($path) . ' PNG:' . escapeshellarg($pngOut) . ' 2>/dev/null';
		exec($cmd, $unused, $exitCode);
		if ($exitCode !== 0 || !is_file($pngOut) || filesize($pngOut) === 0) {
			@unlink($pngOut);
			return null;
		}
		$png = @file_get_contents($pngOut);
		@unlink($pngOut);
		return ($png !== false && $png !== '') ? $png : null;
	}

	/**
	 * @return string|null
	 */
	protected static function convertWebpBytesToPngViaMagick(string $raw): ?string
	{
		$tmp = tempnam(sys_get_temp_dir(), 'fc_webp_');
		if ($tmp === false) {
			return null;
		}
		$webpPath = $tmp . '.webp';
		@unlink($tmp);
		if (@file_put_contents($webpPath, $raw) === false) {
			@unlink($webpPath);
			return null;
		}
		$png = static::convertImagePathToPngViaMagick($webpPath);
		@unlink($webpPath);
		return $png;
	}

	/**
	 * @return string|null Absolute path to ImageMagick convert
	 */
	protected static function findImageMagickConvertBinary(): ?string
	{
		$candidates = ['/usr/bin/convert', '/usr/local/bin/convert'];
		foreach ($candidates as $path) {
			if (is_executable($path)) {
				return $path;
			}
		}
		return null;
	}

	/**
	 * Data URI for templates (reads .base64 sidecar or generates it once).
	 */
	public static function getLogoDataUri(string $fileName): string
	{
		if ($fileName === '' || !is_file(static::getLogoFilesystemPath($fileName))) {
			return '';
		}
		$sidecarFull = static::getLogoBase64SidecarPath($fileName);
		$dataUri = '';
		if (is_readable($sidecarFull)) {
			$dataUri = trim((string) @file_get_contents($sidecarFull));
		}
		$needsRegenerate = $dataUri === ''
			|| strpos($dataUri, 'data:') !== 0
			|| stripos($dataUri, 'data:image/webp') === 0;
		if ($needsRegenerate) {
			static::writeLogoBase64Sidecar($fileName);
			if (is_readable($sidecarFull)) {
				$dataUri = trim((string) @file_get_contents($sidecarFull));
			}
		}
		return ($dataUri !== '' && strpos($dataUri, 'data:') === 0) ? $dataUri : '';
	}

	public static function unlinkLogoFileAndSidecar(string $fileName): void
	{
		if ($fileName === '') {
			return;
		}
		$full = static::getLogoFilesystemPath($fileName);
		if (is_file($full)) {
			@unlink($full);
		}
		$sidecar = static::getLogoBase64SidecarPath($fileName);
		if (is_file($sidecar)) {
			@unlink($sidecar);
		}
	}

	/**
	 * Inline &lt;img&gt; with data-URI src for PDF/HTML/email templates.
	 */
	public function getLogoImgHtmlForTemplate(string $type): string
	{
		$logoName = \App\Utils\ListViewUtils::decodeHtml($this->get($type));
		if (!$logoName) {
			return '';
		}
		$dataUri = static::getLogoDataUri($logoName);
		if ($dataUri === '') {
			return '';
		}
		$logoTitle = (string) $this->get('name');
		$logoAlt = \App\Runtime\Vtiger_Language_Handler::translate('LBL_COMPANY_LOGO_TITLE');
		$logoHeight = $this->get($type . '_height');
		$heightAttr = $logoHeight ? ' height="' . (int) $logoHeight . 'px"' : '';
		return '<img class="organizationLogo" src="' . \App\Security\Purifier::encodeHtml($dataUri) . '" title="'
			. \App\Security\Purifier::encodeHtml($logoTitle) . '" alt="' . \App\Security\Purifier::encodeHtml($logoAlt) . '"' . $heightAttr . ' />';
	}

	/**
	 * Function to get the instance of the Company model
	 * @param int $id
	 * @return \self
	 */
	public static function getInstanceById($id = false)
	{
		if (Cache::has('CompanyDetail', $id)) {
			return Cache::get('CompanyDetail', $id);
		}
		if ($id) {
			$row = (new \App\Db\Query())->from('s_#__companies')->where(['id' => $id])->one();
		} else {
			$row = (new \App\Db\Query())->from('s_#__companies')->where(['default' => 1])->one();
		}
		$self = new self();
		if ($row) {
			$self->setData($row);
		}
		Cache::save('CompanyDetail', $id, $self, Cache::LONG);
		return $self;
	}

	/**
	 * Function to get the Company Logo
	 * @return \App\Modules\Base\Models\Image instance
	 */
	public function getLogo($type = false)
	{
		if (Cache::has('CompanyLogo', $type)) {
			return Cache::get('CompanyLogo', $type);
		}
		$logoName = \App\Utils\ListViewUtils::decodeHtml($this->get($type ? $type : 'logo_main'));
		if (!$logoName) {
			return null;
		}
		$imagePath = static::getLogoFilesystemPath($logoName);
		if (!is_file($imagePath)) {
			return null;
		}
		$logoModel = new \App\Modules\Base\Models\Image();
		$imageURL = \App\Core\AppConfig::main('site_URL') . static::getLogoWebPath($logoName);
		$logoModel->setData([
			'imageUrl' => $imageURL,
			'imagePath' => $imagePath,
			'alt' => $logoName,
			'imageName' => $logoName,
			'title' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_COMPANY_LOGO_TITLE'),
		]);
		Cache::save('CompanyLogo', $type, $logoModel);
		return $logoModel;
	}
}
