/*+***********************************************************************************
 * FreeCRM - IMAP folder tree after Test connection
 *************************************************************************************/

jQuery.Class('Mail_ImapFolderPicker_Js', {}, {
	render: function (form, result) {
		var browser = form.find('.js-mail-imap-folder-browser');
		var treeHost = form.find('.js-mail-imap-folder-tree');
		var sentInput = form.find('.js-mail-imap-folder-sent');
		if (!browser.length || !treeHost.length) {
			return;
		}
		var tree = result.folder_tree || [];
		if (!tree.length) {
			browser.hide();
			treeHost.empty();
			return;
		}
		browser.show();
		treeHost.html(this.buildTreeHtml(tree));
		var suggested = result.suggested_sent || '';
		if (suggested && !sentInput.val()) {
			sentInput.val(suggested);
		}
		this.highlightSelected(form);
		var picker = this;
		treeHost.find('.js-mail-imap-folder-pick').on('click', function (e) {
			e.preventDefault();
			var name = jQuery(e.currentTarget).data('name');
			if (name) {
				sentInput.val(name);
			}
			picker.highlightSelected(form);
		});
		sentInput.off('input.mailFolderPicker').on('input.mailFolderPicker', function () {
			picker.highlightSelected(form);
		});
	},

	highlightSelected: function (form) {
		var val = form.find('.js-mail-imap-folder-sent').val();
		form.find('.js-mail-imap-folder-pick').each(function () {
			var btn = jQuery(this);
			btn.toggleClass('active', btn.data('name') === val);
		});
	},

	buildTreeHtml: function (nodes, depth) {
		depth = depth || 0;
		if (!nodes || !nodes.length) {
			return '';
		}
		var html = '<ul class="list-unstyled mail-imap-folder-tree' + (depth ? ' mail-imap-folder-tree--nested' : '') + '">';
		var self = this;
		jQuery.each(nodes, function (_i, node) {
			var name = node.name || '';
			var path = node.path || node.full_name || name;
			html += '<li class="mail-imap-folder-tree__item">';
			html += '<button type="button" class="btn btn-link btn-xs js-mail-imap-folder-pick" data-name="' + app.htmlEncode(name) + '">';
			html += '<span class="mail-imap-folder-tree__name">' + app.htmlEncode(name) + '</span>';
			html += '</button>';
			if (path && path !== name) {
				html += ' <span class="text-muted small mail-imap-folder-tree__path">(' + app.htmlEncode(path) + ')</span>';
			}
			if (node.children && node.children.length) {
				html += self.buildTreeHtml(node.children, depth + 1);
			}
			html += '</li>';
		});
		html += '</ul>';
		return html;
	}
});
