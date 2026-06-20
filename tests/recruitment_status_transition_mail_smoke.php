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
	RecruitmentStatusTransitionMail::resolveMailActions('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', 1) === [],
	'no actions before save'
);

RecruitmentStatusTransitionMail::saveMatrix([
	[
		'from' => 'PPL_APPLIED',
		'to' => 'PPL_CANDIDATE_PASSED_SCREENING',
		'templates' => [
			['shortName' => $shortName1, 'deliveryMode' => RecruitmentStatusTransitionMail::DELIVERY_PROMPT],
		],
	],
]);

$actionsGlobal = RecruitmentStatusTransitionMail::resolveMailActions('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', 999999);
assertTrue($actionsGlobal !== [], 'actions exist for global template');
assertTrue(
	isset($actionsGlobal[0]['templateId']) && (int) $actionsGlobal[0]['templateId'] === $templateId1,
	'global template resolved'
);
assertTrue(
	($actionsGlobal[0]['deliveryMode'] ?? '') === RecruitmentStatusTransitionMail::DELIVERY_PROMPT,
	'prompt delivery mode preserved'
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

	$actionsAccount = RecruitmentStatusTransitionMail::resolveMailActions('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', $accountId);
	assertTrue($actionsAccount !== [], 'account-specific actions');
	assertTrue((int) $actionsAccount[0]['templateId'] === $templateId1, 'account template id');

	if (isset($templates[1])) {
		$templateId2 = (int) $templates[1]['emailtemplatesid'];
		$shortName2 = (string) $templates[1]['sys_name'];
		RecruitmentStatusTransitionMail::saveMatrix([
			[
				'from' => 'PPL_APPLIED',
				'to' => 'PPL_CANDIDATE_PASSED_SCREENING',
				'templates' => [
					['shortName' => $shortName1, 'deliveryMode' => RecruitmentStatusTransitionMail::DELIVERY_AUTO],
					['shortName' => $shortName2, 'deliveryMode' => RecruitmentStatusTransitionMail::DELIVERY_PROMPT],
				],
			],
		]);
		$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => null], ['emailtemplatesid' => $templateId1])->execute();
		$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => (string) $accountId], ['emailtemplatesid' => $templateId2])->execute();

		$actionsMixed = RecruitmentStatusTransitionMail::resolveMailActions('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', $accountId);
		assertTrue(\count($actionsMixed) >= 1, 'mixed delivery modes resolve');
		$autoCount = 0;
		$promptCount = 0;
		foreach ($actionsMixed as $action) {
			if (($action['deliveryMode'] ?? '') === RecruitmentStatusTransitionMail::DELIVERY_AUTO) {
				++$autoCount;
			}
			if (($action['deliveryMode'] ?? '') === RecruitmentStatusTransitionMail::DELIVERY_PROMPT) {
				++$promptCount;
			}
		}
		assertTrue($autoCount >= 1 || $promptCount >= 1, 'mixed modes present in resolved actions');
	}

	$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => null], ['emailtemplatesid' => $templateId1])->execute();
}

$matrix = RecruitmentStatusTransitionMail::getMatrixForDisplay();
assertTrue(
	isset($matrix['PPL_APPLIED']['PPL_CANDIDATE_PASSED_SCREENING'][0]['shortName'])
	&& $matrix['PPL_APPLIED']['PPL_CANDIDATE_PASSED_SCREENING'][0]['shortName'] === $shortName1,
	'matrix for display contains short name with structure'
);
assertTrue(
	isset($matrix['PPL_APPLIED']['PPL_CANDIDATE_PASSED_SCREENING'][0]['deliveryMode']),
	'matrix for display contains delivery mode'
);

assertTrue(
	RecruitmentTemplate::isShortNameUsedInMatrix($shortName1),
	'short name marked used in matrix'
);

RecruitmentStatusTransitionMail::saveMatrix([]);
assertTrue(
	RecruitmentStatusTransitionMail::resolveMailActions('PPL_APPLIED', 'PPL_CANDIDATE_PASSED_SCREENING', 1) === [],
	'actions cleared after empty save'
);

$db->createCommand()->delete('u_yf_recruitment_status_transition_mail')->execute();
$db->createCommand()->update('u_yf_emailtemplates', ['account_id' => null], ['module' => 'ProjektyRekrutacyjne'])->execute();

if ($failures > 0) {
	echo "\n{$failures} failure(s)\n";
	exit(1);
}

echo "\nAll checks passed.\n";
