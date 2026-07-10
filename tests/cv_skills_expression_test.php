<?php
/**
 * One-off smoke test for CV skills boolean expression search — run via:
 * docker compose exec -T app php tests/cv_skills_expression_test.php
 */

declare(strict_types=1);

define('ROOT_DIRECTORY', dirname(__DIR__));
define('REQUEST_MODE', 'TEST');

require_once ROOT_DIRECTORY . '/vendor/autoload.php';

use App\Modules\Candidates\Exceptions\InvalidCvSkillsExpressionException;
use App\Modules\Candidates\Services\CvSkillsAndNode;
use App\Modules\Candidates\Services\CvSkillsExpressionParser;
use App\Modules\Candidates\Services\CvSkillsOrNode;
use App\Modules\Candidates\Services\CvSkillsQueryCompiler;
use App\Modules\Candidates\Services\CvSkillsSearch;
use App\Modules\Candidates\Services\CvSkillsSkillNode;
use yii\db\Expression;

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

function assertInstanceOf(string $class, object $obj, string $msg): void
{
	assertTrue($obj instanceof $class, $msg);
}

function parse(string $raw): \App\Modules\Candidates\Services\CvSkillsExpressionNode
{
	return CvSkillsExpressionParser::parse($raw);
}

$ast = parse('Java AND Spring');
assertInstanceOf(CvSkillsAndNode::class, $ast, 'Java AND Spring is AndNode');
assertTrue(count($ast->children) === 2, 'Java AND Spring has two children');

$ast = parse('Java AND (Spring OR Kotlin)');
assertInstanceOf(CvSkillsAndNode::class, $ast, 'Java AND (Spring OR Kotlin) root is AndNode');
assertInstanceOf(CvSkillsOrNode::class, $ast->children[1], 'second child is OrNode');

$ast = parse('(Java OR Kotlin) AND (Spring OR SQL OR PostgreSQL)');
assertInstanceOf(CvSkillsAndNode::class, $ast, 'double OR groups root is AndNode');
assertInstanceOf(CvSkillsOrNode::class, $ast->children[0], 'first child is OrNode');
assertTrue(count($ast->children[1]->children) === 3, 'second OR group has three terms');

$ast = parse('"Spring Boot" AND Java');
assertInstanceOf(CvSkillsAndNode::class, $ast, 'quoted phrase parses');
assertTrue($ast->children[0] instanceof CvSkillsSkillNode && $ast->children[0]->term === 'Spring Boot', 'quoted term value');

$ast = parse('A OR B AND C');
assertInstanceOf(CvSkillsOrNode::class, $ast, 'A OR B AND C root is OrNode (AND binds tighter)');
assertInstanceOf(CvSkillsSkillNode::class, $ast->children[0], 'A OR B AND C left child is A');
assertInstanceOf(CvSkillsAndNode::class, $ast->children[1], 'A OR B AND C right child is And(B,C)');

$terms = CvSkillsExpressionParser::collectTerms(parse('Java AND (Spring OR Kotlin)'));
assertTrue($terms === ['Java', 'Spring', 'Kotlin'], 'collectTerms preserves unique leaf order');

try {
	parse('');
	assertTrue(false, 'empty expression should throw');
} catch (InvalidCvSkillsExpressionException) {
	echo "OK: empty expression throws\n";
}

try {
	parse('Java AND');
	assertTrue(false, 'trailing AND should throw');
} catch (InvalidCvSkillsExpressionException) {
	echo "OK: trailing AND throws\n";
}

try {
	parse('(Spring OR');
	assertTrue(false, 'unclosed paren should throw');
} catch (InvalidCvSkillsExpressionException) {
	echo "OK: unclosed paren throws\n";
}

$column = 'u_yf_candidatescf.cv_text';
$compiled = CvSkillsQueryCompiler::compile(parse('Java AND Spring'), $column);
assertTrue($compiled instanceof Expression, 'Java AND Spring compiles to Expression (FULLTEXT fast-path)');

$compiled = CvSkillsQueryCompiler::compile(parse('Java AND (Spring OR Kotlin)'), $column);
assertTrue($compiled instanceof Expression, 'Java AND (Spring OR Kotlin) compiles to Expression');

$compiled = CvSkillsQueryCompiler::compile(parse('Go'), $column);
assertTrue(is_array($compiled) && ($compiled[0] ?? '') === 'REGEXP', 'short skill compiles to REGEXP');

$compiled = CvSkillsQueryCompiler::compile(parse('Go OR Java'), $column);
assertTrue(is_array($compiled) && ($compiled[0] ?? '') === 'or', 'mixed short OR long uses nested or');

$highlight = CvSkillsSearch::collectTermsForHighlight('Java AND Kotlin');
assertTrue($highlight === ['Java', 'Kotlin'], 'collectTermsForHighlight via facade');

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
