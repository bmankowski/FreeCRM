<?php
/**
 * FreeCRM - Resolves source.*, destination.*, relation.* workflow variables.
 *
 * @package   FreeCRM
 * @author    bmankowski@gmail.com
 * @license   FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Workflow;

class RelationFieldResolver
{
	private RelationWorkflowContext $context;

	public function __construct(RelationWorkflowContext $context)
	{
		$this->context = $context;
	}

	public function resolve(string $reference): string
	{
		$reference = trim($reference);
		if ($reference === '') {
			return '';
		}
		if (!str_contains($reference, '.')) {
			return '';
		}
		[$namespace, $field] = explode('.', $reference, 2);
		switch ($namespace) {
			case 'source':
				return $this->resolveRecordField($this->context->getSourceRecordModel(), $field);
			case 'destination':
				return $this->resolveRecordField($this->context->getDestinationRecordModel(), $field);
			case 'relation':
				return $this->resolveRelationField($field);
		}
		return '';
	}

	public function replaceInContent(string $content): string
	{
		return (string) preg_replace_callback(
			'/\$((?:source|destination|relation)\.[\w]+)\$/',
			function (array $matches): string {
				return $this->resolve($matches[1]);
			},
			$content
		);
	}

	private function resolveRecordField(\App\Modules\Base\Models\Record $record, string $fieldName): string
	{
		if ($fieldName === 'RecordLabel') {
			return (string) $record->getName();
		}
		if (!$record->has($fieldName)) {
			return '';
		}
		$value = $record->get($fieldName);
		if (is_array($value)) {
			return implode(', ', $value);
		}
		return (string) $value;
	}

	private function resolveRelationField(string $fieldName): string
	{
		switch ($fieldName) {
			case 'sourceStatus':
				return $this->context->getSourceStatus();
			case 'destinationStatus':
				return $this->context->getDestinationStatus();
			case 'sourceStatusLabel':
				return \App\Language::translate($this->context->getSourceStatus(), 'ProjektyRekrutacyjne');
			case 'destinationStatusLabel':
				return \App\Language::translate($this->context->getDestinationStatus(), 'ProjektyRekrutacyjne');
			default:
				$value = $this->context->getRelationValue($fieldName);
				if ($fieldName === $this->context->getRelationField() && is_string($value) && $value !== '') {
					return \App\Language::translate($value, 'ProjektyRekrutacyjne');
				}
				return is_scalar($value) ? (string) $value : '';
		}
	}
}
