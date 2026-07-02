/*+***********************************************************************************
 * FreeCRM - reference field autocomplete (EditView, quick create, mass edit, modals)
 *************************************************************************************/

(function ($) {
	'use strict';

	function escapeHtml(text) {
		return String(text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function highlightTerm(text, term) {
		if (!text) {
			return '';
		}
		var escaped = escapeHtml(text);
		if (!term) {
			return escaped;
		}
		var re = new RegExp('(' + escapeHtml(term).replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
		return escaped.replace(re, '<strong class="reference-ac-highlight">$1</strong>');
	}

	$.widget('custom.referenceAutocomplete', $.ui.autocomplete, {
		options: {
			keepOpenOnBlurForTesting: true
		},
		close: function (event) {
			if (this.options.keepOpenOnBlurForTesting && event && event.type === 'blur') {
				return;
			}
			this._super(event);
		},
		_renderItem: function (ul, item) {
			if (item.type === 'no results') {
				return $('<li class="reference-ac-item reference-ac-item--empty">')
					.append($('<div class="reference-ac-empty">').text(item.label))
					.appendTo(ul);
			}

			var term = this.term || '';
			var title = item.value || item.label;
			var $row = $('<div class="reference-ac-row">');

			$row.append($('<span class="reference-ac-title">').html(highlightTerm(title, term)));

			if (item.subtitle) {
				$row.append($('<span class="reference-ac-subtitle">').html(highlightTerm(item.subtitle, term)));
			}

			return $('<li class="reference-ac-item">')
				.data('item.autocomplete', item)
				.append($('<a>').append($row))
				.appendTo(ul);
		},
		_suggest: function (items) {
			this._super(items);
			this.menu.element
				.addClass('reference-ac')
				.css({
					'z-index': 100001,
					'min-width': this.element.outerWidth()
				});
		}
	});

	jQuery.Class('Base_ReferenceAutocomplete_Js', {}, {
		attach: function (container, editInstance) {
			container.find('input.autoComplete').each(function () {
				var $input = $(this);
				if ($input.data('reference-ac-bound')) {
					return;
				}
				$input.data('reference-ac-bound', true);
				$input.referenceAutocomplete({
					delay: 600,
					minLength: 3,
					source: function (request, response) {
						var inputElement = $(this.element[0]);
						var params = editInstance.getReferenceSearchParams(inputElement);
						params.search_value = request.term;
						editInstance.searchModuleNames(params).then(function (data) {
							var responseDataList = [];
							var serverDataFormat = data.result;
							if (serverDataFormat.length <= 0) {
								serverDataFormat = [{
									label: app.vtranslate('JS_NO_RESULTS_FOUND'),
									type: 'no results'
								}];
							}
							for (var id in serverDataFormat) {
								responseDataList.push(serverDataFormat[id]);
							}
							response(responseDataList);
						});
					},
					select: function (event, ui) {
						var selectedItemData = ui.item;
						if (typeof selectedItemData.type !== 'undefined' && selectedItemData.type === 'no results') {
							return false;
						}
						selectedItemData.name = selectedItemData.value;
						var element = $(this);
						var tdElement = element.closest('.fieldValue');
						editInstance.setReferenceFieldValue(tdElement, selectedItemData);

						var sourceField = tdElement.find('input[class="sourceField"]').attr('name');
						var fieldElement = tdElement.find('input[name="' + sourceField + '"]');
						fieldElement.trigger(Vtiger_Edit_Js.postReferenceSelectionEvent, {data: selectedItemData});
					}
				});
			});
		}
	});
}(jQuery));
