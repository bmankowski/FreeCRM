<?php
/**
 * Smoke test for RecruitmentStatusTransitionMail — run via:
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

$templates = \App\Email\Mail::getTempleteList('ProjektyRekrutacyjne');
if ($templates === []) {
	echo "SKIP: no ProjektyRekrutacyjne email templates in DB\n";
	exit(0);
}

$id1 = (int) $templates[0]['id'];
$id2 = isset($templates[1]) ? (int) $templates[1]['id'] : $id1;

$db = \App\Db\Db::getInstance();
$db->createCommand()->delete('u_yf_recruitment_status_transition_mail')->execute();

assertTrue(
	RecruitmentStatusTransitionMail::getPrompt('PPL_CANDIDATE_PASSED_SCREENING', 'PPL_WAITING_FOR_INTERVIEW') === null,
	'no prompt before save'
);

RecruitmentStatusTransitionMail::saveMatrix([
	[
		'from' => 'PPL_CANDIDATE_PASSED_SCREENING',
		'to' => 'PPL_WAITING_FOR_INTERVIEW',
		'templateIds' => [$id1, $id2],
	],
]);

$prompt = RecruitmentStatusTransitionMail::getPrompt('PPL_CANDIDATE_PASSED_SCREENING', 'PPL_WAITING_FOR_INTERVIEW');
assertTrue($prompt !== null, 'prompt exists after save');
assertTrue(isset($prompt['templateIds']) && count($prompt['templateIds']) >= 1, 'prompt has template ids');
assertTrue($prompt['templateIds'][0] === $id1, 'first template id order preserved');

$matrix = RecruitmentStatusTransitionMail::getMatrixForDisplay();
assertTrue(
	isset($matrix['PPL_CANDIDATE_PASSED_SCREENING']['PPL_WAITING_FOR_INTERVIEW'])
	&& count($matrix['PPL_CANDIDATE_PASSED_SCREENING']['PPL_WAITING_FOR_INTERVIEW']) >= 1,
	'matrix for display contains saved pair'
);

RecruitmentStatusTransitionMail::saveMatrix([]);
assertTrue(
	RecruitmentStatusTransitionMail::getPrompt('PPL_CANDIDATE_PASSED_SCREENING', 'PPL_WAITING_FOR_INTERVIEW') === null,
	'prompt cleared after empty save'
);

$db->createCommand()->delete('u_yf_recruitment_status_transition_mail')->execute();

if ($failures > 0) {
	echo "\n{$failures} failure(s)\n";
	exit(1);
}

echo "\nAll checks passed.\n";
