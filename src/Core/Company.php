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
		$dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($raw);
		return false !== @file_put_contents(static::getLogoBase64SidecarPath($fileName), $dataUri, LOCK_EX);
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
		if ($dataUri === '' || strpos($dataUri, 'data:') !== 0) {
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
