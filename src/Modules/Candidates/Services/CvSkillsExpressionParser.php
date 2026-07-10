<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Candidates\Services;

use App\Modules\Candidates\Exceptions\InvalidCvSkillsExpressionException;

interface CvSkillsExpressionNode
{
}

final readonly class CvSkillsSkillNode implements CvSkillsExpressionNode
{
	public function __construct(public string $term)
	{
	}
}

final readonly class CvSkillsAndNode implements CvSkillsExpressionNode
{
	/**
	 * @param list<CvSkillsExpressionNode> $children
	 */
	public function __construct(public array $children)
	{
	}
}

final readonly class CvSkillsOrNode implements CvSkillsExpressionNode
{
	/**
	 * @param list<CvSkillsExpressionNode> $children
	 */
	public function __construct(public array $children)
	{
	}
}

class CvSkillsExpressionParser
{
	private const TOKEN_TERM = 'TERM';
	private const TOKEN_AND = 'AND';
	private const TOKEN_OR = 'OR';
	private const TOKEN_LPAREN = 'LPAREN';
	private const TOKEN_RPAREN = 'RPAREN';
	private const TOKEN_EOF = 'EOF';

	private string $input = '';

	/** @var list<array{type: string, value: string}> */
	private array $tokens = [];

	private int $position = 0;

	public static function parse(string $raw): CvSkillsExpressionNode
	{
		$parser = new self();
		return $parser->parseExpression(trim($raw));
	}

	/**
	 * @return list<string>
	 */
	public static function collectTerms(CvSkillsExpressionNode $node): array
	{
		$terms = [];
		self::collectTermsRecursive($node, $terms);
		$unique = [];
		foreach ($terms as $term) {
			if (!in_array($term, $unique, true)) {
				$unique[] = $term;
			}
		}

		return $unique;
	}

	/**
	 * @param list<string> $terms
	 */
	private static function collectTermsRecursive(CvSkillsExpressionNode $node, array &$terms): void
	{
		if ($node instanceof CvSkillsSkillNode) {
			$terms[] = $node->term;
			return;
		}
		if ($node instanceof CvSkillsAndNode || $node instanceof CvSkillsOrNode) {
			foreach ($node->children as $child) {
				self::collectTermsRecursive($child, $terms);
			}
		}
	}

	private function parseExpression(string $raw): CvSkillsExpressionNode
	{
		if ($raw === '') {
			throw new InvalidCvSkillsExpressionException(detail: 'empty expression');
		}

		$this->input = $raw;
		$this->tokens = $this->tokenize($raw);
		$this->position = 0;

		$node = $this->parseOrExpr();
		$this->expect(self::TOKEN_EOF);

		return $node;
	}

	private function parseOrExpr(): CvSkillsExpressionNode
	{
		$children = [$this->parseAndExpr()];
		while ($this->match(self::TOKEN_OR)) {
			$children[] = $this->parseAndExpr();
		}

		if (count($children) === 1) {
			return $children[0];
		}

		return new CvSkillsOrNode($children);
	}

	private function parseAndExpr(): CvSkillsExpressionNode
	{
		$children = [$this->parsePrimary()];
		while ($this->match(self::TOKEN_AND)) {
			$children[] = $this->parsePrimary();
		}

		if (count($children) === 1) {
			return $children[0];
		}

		return new CvSkillsAndNode($children);
	}

	private function parsePrimary(): CvSkillsExpressionNode
	{
		if ($this->match(self::TOKEN_LPAREN)) {
			$node = $this->parseOrExpr();
			if (!$this->match(self::TOKEN_RPAREN)) {
				throw new InvalidCvSkillsExpressionException(detail: 'missing closing parenthesis');
			}

			return $node;
		}

		$token = $this->peek();
		if ($token['type'] !== self::TOKEN_TERM) {
			throw new InvalidCvSkillsExpressionException(
				detail: $token['type'] === self::TOKEN_EOF ? 'unexpected end of expression' : 'expected skill term'
			);
		}

		$this->advance();

		return new CvSkillsSkillNode($token['value']);
	}

	/**
	 * @return list<array{type: string, value: string}>
	 */
	private function tokenize(string $raw): array
	{
		$tokens = [];
		$length = strlen($raw);
		$offset = 0;

		while ($offset < $length) {
			if (ctype_space($raw[$offset])) {
				++$offset;
				continue;
			}

			if ($raw[$offset] === '(') {
				$tokens[] = ['type' => self::TOKEN_LPAREN, 'value' => '('];
				++$offset;
				continue;
			}

			if ($raw[$offset] === ')') {
				$tokens[] = ['type' => self::TOKEN_RPAREN, 'value' => ')'];
				++$offset;
				continue;
			}

			if ($raw[$offset] === '"' || $raw[$offset] === "'") {
				$quote = $raw[$offset];
				++$offset;
				$value = '';
				while ($offset < $length && $raw[$offset] !== $quote) {
					if ($raw[$offset] === '\\' && $offset + 1 < $length) {
						$value .= $raw[$offset + 1];
						$offset += 2;
						continue;
					}
					$value .= $raw[$offset];
					++$offset;
				}
				if ($offset >= $length || $raw[$offset] !== $quote) {
					throw new InvalidCvSkillsExpressionException(detail: 'unclosed quoted string');
				}
				++$offset;
				if ($value === '') {
					throw new InvalidCvSkillsExpressionException(detail: 'empty quoted term');
				}
				$tokens[] = ['type' => self::TOKEN_TERM, 'value' => $value];
				continue;
			}

			if ($this->isKeywordAt($raw, $offset, 'AND')) {
				$tokens[] = ['type' => self::TOKEN_AND, 'value' => 'AND'];
				$offset += 3;
				continue;
			}

			if ($this->isKeywordAt($raw, $offset, 'OR')) {
				$tokens[] = ['type' => self::TOKEN_OR, 'value' => 'OR'];
				$offset += 2;
				continue;
			}

			$term = '';
			while ($offset < $length) {
				if (ctype_space($raw[$offset]) || $raw[$offset] === '(' || $raw[$offset] === ')') {
					break;
				}
				if ($this->isKeywordAt($raw, $offset, 'AND') || $this->isKeywordAt($raw, $offset, 'OR')) {
					break;
				}
				$term .= $raw[$offset];
				++$offset;
			}

			if ($term === '') {
				throw new InvalidCvSkillsExpressionException(detail: 'invalid character near position ' . $offset);
			}

			$tokens[] = ['type' => self::TOKEN_TERM, 'value' => $term];
		}

		$tokens[] = ['type' => self::TOKEN_EOF, 'value' => ''];

		return $tokens;
	}

	private function isKeywordAt(string $raw, int $offset, string $keyword): bool
	{
		$length = strlen($keyword);
		if (substr($raw, $offset, $length) !== $keyword) {
			return false;
		}

		$before = $offset > 0 ? $raw[$offset - 1] : ' ';
		$after = $offset + $length < strlen($raw) ? $raw[$offset + $length] : ' ';

		return !$this->isTermChar($before) && !$this->isTermChar($after);
	}

	private function isTermChar(string $char): bool
	{
		return $char !== ' ' && $char !== "\t" && $char !== "\n" && $char !== "\r"
			&& $char !== '(' && $char !== ')';
	}

	/**
	 * @return array{type: string, value: string}
	 */
	private function peek(): array
	{
		return $this->tokens[$this->position];
	}

	private function advance(): void
	{
		if ($this->position < count($this->tokens) - 1) {
			++$this->position;
		}
	}

	private function match(string $type): bool
	{
		if ($this->peek()['type'] === $type) {
			$this->advance();
			return true;
		}

		return false;
	}

	private function expect(string $type): void
	{
		if ($this->peek()['type'] !== $type) {
			throw new InvalidCvSkillsExpressionException(detail: 'unexpected token');
		}
		$this->advance();
	}
}
