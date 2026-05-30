<?php
/**
 * One-off smoke test for RecruitmentStatusTransition — run via:
 * docker compose exec -T app php tests/recruitment_status_transition_smoke.php
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

use App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition;

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

// Reset to Option B default
$db = \App\Db\Db::getInstance();
$db->createCommand()->delete('u_yf_recruitment_status_transitions')->execute();
$db->createCommand()->update('u_yf_recruitment_settings', ['configured' => 0], ['id' => 1])->execute();

assertTrue(!RecruitmentStatusTransition::isConfigured(), 'not configured after reset');
assertTrue(
	RecruitmentStatusTransition::isAllowed('PPL_APPLIED', 'PPL_ACCEPTED'),
	'all transitions allowed before save'
);
assertTrue(
	count(RecruitmentStatusTransition::getStatusOptions()) === 11,
	'11 status options'
);

$suggested = RecruitmentStatusTransition::getSuggestedDefaults();
assertTrue(isset($suggested['PPL_APPLIED']), 'suggested defaults include PPL_APPLIED');
assertTrue(
	in_array('PPL_REJECTED_AFTER_CV', $suggested['PPL_APPLIED'], true),
	'suggested default includes reject from applied'
);

RecruitmentStatusTransition::saveMatrix([
	['from' => 'PPL_APPLIED', 'to' => 'PPL_REJECTED_AFTER_CV'],
	['from' => 'PPL_APPLIED', 'to' => 'PPL_CANDIDATE_PASSED_SCREENING'],
]);

assertTrue(RecruitmentStatusTransition::isConfigured(), 'configured after save');
assertTrue(
	RecruitmentStatusTransition::isAllowed('PPL_APPLIED', 'PPL_REJECTED_AFTER_CV'),
	'whitelisted transition allowed'
);
assertTrue(
	!RecruitmentStatusTransition::isAllowed('PPL_APPLIED', 'PPL_ACCEPTED'),
	'non-whitelisted transition blocked'
);

$map = RecruitmentStatusTransition::getAdjacencyMap();
assertTrue(
	isset($map['PPL_APPLIED']) && count($map['PPL_APPLIED']) === 2,
	'adjacency map has two targets from applied'
);

// Reset for production Option B state
$db->createCommand()->delete('u_yf_recruitment_status_transitions')->execute();
$db->createCommand()->update('u_yf_recruitment_settings', ['configured' => 0], ['id' => 1])->execute();

echo $failures === 0 ? "\nAll service smoke tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
