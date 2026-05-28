/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
Vtiger_ListView_Js('DocumentTemplates_ListView_Js', {}, {
	getSourceModuleParam: function () {
		var sourceModule = jQuery('#sourceModule').val();
		if (sourceModule) {
			return sourceModule;
		}
		var match = window.location.search.match(/(?:^|[?&])sourceModule=([^&]+)/);
		return match ? decodeURIComponent(match[1]) : '';
	},
	getDefaultParams: function () {
		var params = this._super();
		var sourceModule = this.getSourceModuleParam();
		if (sourceModule) {
			params.sourceModule = sourceModule;
		}
		return params;
	}
});
