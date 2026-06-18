<?php
/**
 * Smoke test for RecruitmentStatusTransitionMail + RecruitmentTemplate — run via:
 * docker compose exec -T app php tests/recruitment_status_transition_mail_smoke.php
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

use App\Modules\EmailTemplates\Models\RecruitmentTemplate;
use App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransitionMail;

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

$templates = (new \App\Db\Query())
	->select(['emailtemplatesid', 'sys_name'])
	->from('u_yf_emailtemplates')
	->where(['module' => 'ProjektyRekrutacyjne'])
	->andWhere(['not', ['sys_name' => null]])
	->andWhere(['<>', 'sys_name', ''])
	->limit(3)
	->all();

if ($templates === []) {
	echo "SKIP: no ProjektyRekrutacyjne templates with sys_name\n";
	exit(0);
}

$shortName1 = (string) $templates[0]['sys_name'];
$templateId1 = (int) $templates[0]['emailtemplatesid'];

$db = \App\Db\Db::getInstance();
$db->createCommand()->delete('u_yf_recruitment_status_transition_mail')->execute();
$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => null], ['module' => 'ProjektyRekrutacyjne'])->execute();

assertTrue(
	RecruitmentStatusTransitionMail::getPrompt('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', 1) === null,
	'no prompt before save'
);

RecruitmentStatusTransitionMail::saveMatrix([
	[
		'from' => 'PPL_APPLIED',
		'to' => 'PPL_CANDIDATE_PASSED_SCREENING',
		'shortNames' => [$shortName1],
	],
]);

$promptGlobal = RecruitmentStatusTransitionMail::getPrompt('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', 999999);
assertTrue($promptGlobal !== null, 'prompt exists for global template');
assertTrue(
	isset($promptGlobal['templateIds'][0]) && (int) $promptGlobal['templateIds'][0] === $templateId1,
	'global template resolved'
);

$accountId = (new \App\Db\Query())
	->select(['accountid'])
	->from('vtiger_account')
	->innerJoin('vtiger_crmentity', 'vtiger_account.accountid = vtiger_crmentity.crmid')
	->where(['vtiger_crmentity.deleted' => 0])
	->scalar();

if ($accountId) {
	$accountId = (int) $accountId;
	$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => (string) $accountId], ['emailtemplatesid' => $templateId1])->execute();

	$promptAccount = RecruitmentStatusTransitionMail::getPrompt('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', $accountId);
	assertTrue($promptAccount !== null, 'account-specific prompt');
	assertTrue((int) $promptAccount['templateIds'][0] === $templateId1, 'account template id');

	if (isset($templates[1])) {
		$templateId2 = (int) $templates[1]['emailtemplatesid'];
		$shortName2 = (string) $templates[1]['sys_name'];
		RecruitmentStatusTransitionMail::saveMatrix([
			[
				'from' => 'PPL_APPLIED',
				'to' => 'PPL_CANDIDATE_PASSED_SCREENING',
				'shortNames' => [$shortName1, $shortName2],
			],
		]);
		$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => null], ['emailtemplatesid' => $templateId1])->execute();
		$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => (string) $accountId], ['emailtemplatesid' => $templateId2])->execute();

		$promptMulti = RecruitmentStatusTransitionMail::getPrompt('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', $accountId);
		assertTrue($promptMulti !== null && count($promptMulti['templateIds']) >= 1, 'multi short name resolves');
	}

	$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => null], ['emailtemplatesid' => $templateId1])->execute();
}

$matrix = RecruitmentStatusTransitionMail::getMatrixForDisplay();
assertTrue(
	isset($matrix['PPL_APPLIED']['PPL_CANDIDATE_PASSED_SCREENING'])
	&& in_array($shortName1, $matrix['PPL_APPLIED']['PPL_CANDIDATE_PASSED_SCREENING'], true),
	'matrix for display contains short name'
);

assertTrue(
	RecruitmentTemplate::isShortNameUsedInMatrix($shortName1),
	'short name marked used in matrix'
);

RecruitmentStatusTransitionMail::saveMatrix([]);
assertTrue(
	RecruitmentStatusTransitionMail::getPrompt('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', 1) === null,
	'prompt cleared after empty save'
);

$db->createCommand()->delete('u_yf_recruitment_status_transition_mail')->execute();
$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => null], ['module' => 'ProjektyRekrutacyjne'])->execute();

if ($failures > 0) {
	echo "\n{$failures} failure(s)\n";
	exit(1);
}

echo "\nAll checks passed.\n";
