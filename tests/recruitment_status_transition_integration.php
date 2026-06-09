<?php
/**
 * Integration smoke test for changeStatus + transition rules.
 * docker compose exec -T app php tests/recruitment_status_transition_integration.php
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

$db = \App\Db\Db::getInstance();

// Find a real project-candidate pair with PPL_APPLIED status
$row = (new \App\Db\Query())
	->select(['crmid', 'relcrmid', 'recruitment_status_rel'])
	->from('u_yf_projekty_rekrutacyjne_relations_members_entity')
	->where(['recruitment_status_rel' => 'PPL_APPLIED'])
	->limit(1)
	->one();

if (!$row) {
	echo "SKIP: no PPL_APPLIED relation row found for integration test\n";
	exit(0);
}

$projectId = (int) $row['crmid'];
$candidateId = (int) $row['relcrmid'];
$originalStatus = (string) $row['recruitment_status_rel'];

$relation = \App\Modules\Base\Models\Relation::getInstance(
	\App\Modules\Base\Models\Module::getInstance('ProjektyRekrutacyjne'),
	\App\Modules\Base\Models\Module::getInstance('Candidates')
)->getTypeRelationModel();

/** @var \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers $relation */

// Configure strict rules: only reject allowed from applied
RecruitmentStatusTransition::saveMatrix([
	['from' => 'PPL_APPLIED', 'to' => 'PPL_REJECTED_AFTER_CV'],
]);

// Illegal transition should fail
$resultIllegal = $relation->changeStatus($projectId, $candidateId, 'PPL_APPLIED', 'PPL_ACCEPTED');
assertTrue($resultIllegal === false, 'changeStatus blocks illegal PPL_APPLIED -> PPL_ACCEPTED');

$afterIllegal = $relation->getRelationData($projectId, $candidateId);
assertTrue(
	($afterIllegal['recruitment_status_rel'] ?? '') === 'PPL_APPLIED',
	'status unchanged after illegal changeStatus'
);

// Whitelisted pair passes isAllowed when configured
assertTrue(
	RecruitmentStatusTransition::isAllowed('PPL_APPLIED', 'PPL_REJECTED_AFTER_CV'),
	'isAllowed permits whitelisted transition'
);
assertTrue(
	!RecruitmentStatusTransition::isAllowed('PPL_APPLIED', 'PPL_ACCEPTED'),
	'isAllowed blocks non-whitelisted transition when configured'
);

// Full legal changeStatus path requires web user context (project counter save); verified manually in browser.

// Reset rules to Option B default
$db->createCommand()->delete('u_yf_recruitment_status_transitions')->execute();
$db->createCommand()->update('u_yf_recruitment_settings', ['configured' => 0], ['id' => 1])->execute();

echo $failures === 0 ? "\nAll integration tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
