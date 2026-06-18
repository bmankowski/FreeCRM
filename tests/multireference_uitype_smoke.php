<?php
/**
 * Smoke test for MultiReference uitype 306.
 * Run: docker compose exec -T app php tests/multireference_uitype_smoke.php
 */

declare(strict_types=1);

define('ROOT_DIRECTORY', dirname(__DIR__));
define('REQUEST_MODE', 'TEST');

require_once ROOT_DIRECTORY . '/vendor/autoload.php';
require_once ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require_once ROOT_DIRECTORY . '/config/api.php';
require_once ROOT_DIRECTORY . '/config/config.php';
\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();
\App\EntryPoint\WebUI::initialize();
\App\User\CurrentUser::setContextUserId(1);

use App\Modules\Base\Models\Field as FieldModel;
use App\Modules\Base\UiTypes\MultiReference;

$failures = 0;

function assertTrue(bool $cond, string $msg): void
{
	global $failures;
	if (!$cond) {
		echo "FAIL: $msg\n";
		++$failures;
	} else {
		echo "OK: $msg\n";
	}
}

assertTrue(MultiReference::parseIds('10,20,10,') === [10, 20], 'parseIds dedupes and filters');

$wsRow = (new \App\Db\Query())->from('vtiger_ws_fieldtype')->where(['uitype' => 306])->one();
assertTrue(is_array($wsRow) && ($wsRow['fieldtype'] ?? '') === 'multiReference', 'vtiger_ws_fieldtype uitype 306');

$fieldModel = new FieldModel();
$fieldModel->set('uitype', 306);
assertTrue($fieldModel->getUiTypeName() === 'multiReference', 'SEMANTIC_TYPE_MAP');

$uiType = \App\Modules\Base\UiTypes\BaseUiType::getInstanceFromField($fieldModel);
assertTrue($uiType instanceof MultiReference, 'UITYPE_CLASS_MAP resolves MultiReference');

assertTrue(class_exists(\App\QueryField\MultiReferenceField::class), 'MultiReferenceField QueryField exists');

$accountField = \App\Modules\Base\Models\Field::getInstance('account_id', \App\Modules\Base\Models\Module::getInstance('EmailTemplates'));
if ($accountField) {
	$refList = $accountField->getReferenceList();
	assertTrue(in_array('Accounts', $refList, true), 'EmailTemplates account_id resolves Accounts from fieldmodulerel');
}

exit($failures > 0 ? 1 : 0);
